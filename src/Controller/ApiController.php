<?php
namespace App\Controller;

//use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

//use Symfony\Component\Validator\Validator\ValidatorInterface;


class ApiController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(
        Request $request
        //ManagerRegistry $doctrine,
        //ValidatorInterface $validator,
    ): Response
    {
        return JsonResponse::fromJsonString('{ "data": 123 }');
    }
}
