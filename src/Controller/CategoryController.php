<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Film;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CategoryController extends AbstractController
{
    /**
    * @Route("/category", name ="category")
    */
    public function category(CategoryRepository $cat, Request $request, PaginatorInterface $paginator)
    {        
        $categories = $cat->findAll();
        $total = count($categories);

        $pages = $paginator->paginate(
            $categories,
            $request->query->getInt('page', 1),
            4
        );

        return $this->render('category/index.html.twig', [
            'controller_name' => 'CategoryController',
            'categories' => $categories,
            'total' => $total
        ]);
    }

    /**
    * @Route("/category/new", name="category_create")
    */
    public function create(Request $request, EntityManagerInterface $manager)
    {        
        $category = new Category();

        $form = $this->createFormBuilder($category)
                     ->add('title', TextType::class, [
                        'attr' => [
                            'placeholder' => "Titre de la catégorie",
                            ]
                        ])
                     ->add('description')
                     ->getForm();

        $form->handleRequest($request);

        dump($category);

        if($form->isSubmitted() && $form->isValid()){
            $manager->persist($category);
            $manager->flush();

            return $this->redirectToRoute('category');
        }

        return $this->render('category/create.html.twig', [
            'formCategory' => $form->createView()
        ]);
    }
}
