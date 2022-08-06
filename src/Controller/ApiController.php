<?php
namespace App\Controller;

use App\Entity\Test;
use App\Entity\Voucher;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\ByteString;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class ApiController extends AbstractController
{
    /**
     * @Route("/generate", methods={"POST"})
     */
    public function generate(
        Request $request,
        ManagerRegistry $doctrine,
        ValidatorInterface $validator
    ) : Response
    {
        $entityManager = $doctrine->getManager();
        $json = $request->getContent();
        $jsonDecoded =  json_decode($json, true);
        $discount = $jsonDecoded['discount'];

        $voucher = new Voucher();
        $voucher->setDiscount($discount);
        $code = ByteString::fromRandom(Voucher::CODE_LENGTH, implode('', range('A', 'Z')))
            ->toString();
        $voucher->setCode($code);

        $entityManager->persist($voucher);
        $entityManager->flush();

        $response = [
            "code" => $voucher->getCode()
        ];

        return new JsonResponse($response);
    }

    /**
     * @Route("/apply", methods={"POST"})
     */
    public function apply(
        Request $request,
        ManagerRegistry $doctrine
    ) : Response
    {
        $json = $request->getContent();
        $jsonDecoded =  json_decode($json, true);
        $code =  $jsonDecoded['code'];
        $items = $jsonDecoded['items'];

        $response = [];

        $voucher = $doctrine
            ->getRepository(Voucher::class)
            ->findOneBy(['code' => $code]);

        if (!empty($voucher)) {
            $response = $this->calculatePriceWithDiscount($items, $voucher);
        }

        return new JsonResponse($response);
    }

    /**
     * Calculate price with discount
     *
     * @param $items
     * @param $voucher
     *
     * @return array
     */
    private function calculatePriceWithDiscount($items, $voucher):array
    {
        $response['code'] = $voucher->getCode();
        $sum = 0;
        $totalDiscount = 0;
        foreach ($items as $item) {
            $sum = $sum + $item['price'];
        }

        for ($i = 0; $i < count($items); $i++) {
            $item = $items[$i];
            $priceWithDiscount = $item['price'];

            if ($totalDiscount != $voucher->getDiscount()) {
                $roundedDiscount = round($voucher->getDiscount() * ($item['price'] / $sum));

                if ($totalDiscount + $roundedDiscount >= $voucher->getDiscount()) {
                    $roundedDiscount = $voucher->getDiscount() - $totalDiscount;
                }

                $priceWithDiscount = $item['price'] - $roundedDiscount;

                if ($priceWithDiscount < 0) {
                    $priceWithDiscount = 0;
                }
                $totalDiscount += $roundedDiscount;
            }

            $response['items'][] = [
                'id' => $item['id'],
                'price' => $item['price'],
                'price_with_discount' => $priceWithDiscount
            ];
        }

        //докидываем скидку если осталась
        if ($totalDiscount < $voucher->getDiscount()) {
            $leftDiscount = $voucher->getDiscount() - $totalDiscount;
            foreach ($response['items'] as &$item) {
                $canAdd = $item['price'] - $item['price_with_discount'];

                if ($leftDiscount - $canAdd >=0) {
                    $item['price_with_discount'] = 0;
                    $totalDiscount += $canAdd;
                } else {
                    $item['price_with_discount'] =  $item['price_with_discount'] - $leftDiscount;
                    break;
                }
            }
        }

        return $response;
    }
}
