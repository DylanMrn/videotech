<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Film;
use App\Entity\Category;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\FilmRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class FilmController extends AbstractController
{
    /**
    * @Route("/film", name ="film")
    */
    public function index(FilmRepository $repo, Request $request, PaginatorInterface $paginator)
    {
        
        $films = $repo->findAll();
        if(!$films){
            $total = 0;
        }
        else {
            $total = count($films);
        }
        

        $pages = $paginator->paginate(
            $films,
            $request->query->getInt('page', 1),
            4
        );

        return $this->render('film/index.html.twig', [
            'controller_name' => 'FilmController',
            'films' => $pages,
            'total' => $total
        ]);
    }

    /**
    * @Route("/", name ="home")
    */
    public function home(FilmRepository $repo) {
        $films = $repo->findAll();
        $total = count($films);

        return $this->render('film/home.html.twig', [
            'title' => 'Page des films',
            'total' => $total
        ]);
    }

    /**
    * @Route("/film/new", name="film_create")
    * @Route("/film/{id}/edit", name="film_edit")
    */
    public function create(FilmRepository $repo, Film $film = null, Request $request, EntityManagerInterface $manager){

        $films = $repo->findAll();
        $total = count($films);

        if(!$film) {
            $film = new Film();
        }

        $form = $this->createFormBuilder($film)
                     ->add('title', TextType::class, [
                        'attr' => [
                            'placeholder' => "Titre du film",
                            'minlength' => 10]
                        ], [
                        // ...
                        'invalid_message' => 'You entered an invalid value, it should include %num% letters',
                        'invalid_message_parameters' => ['%num%' => 10]
                        ])
                     ->add('category', EntityType::class, [
                         'class' => Category::class,
                         'choice_label' => 'title'
                     ])
                     ->add('content', TextType::class, [
                        'attr' => [
                            'placeholder' => "description du film" ]
                     ])
                     ->add('image')    
                     ->getForm();

                     $form->handleRequest($request);

                    if($form->isSubmitted() && $form->isValid()){
                        if(!$film->getId()){
                            $film->setCreatedAt(new \DateTime());
                        }

                        $manager->persist($film);
                        $manager->flush();

                        return $this->redirectToRoute('film_show', ['id'=> $film->getId()]);
                    }

        return $this->render('film/create.html.twig', [
            'formFilm' => $form->createView(),
            'button' => $film->getId() !== null,
            'total' => $total
        ]);
    }

    /**
    * @Route("/film/{id}/delete", name ="film_delete")
    */
    public function delete($id, EntityManagerInterface $manager) {
        $repo = $this->getDoctrine()->getRepository(Film::class);

        $films = $repo->findAll();
        $total = count($films);

        $repo = $repo->find($id);

        $manager->remove($repo);
        dump($repo);

        $manager->flush();

        return $this->render('film/index.html.twig', [
            'total' => $total,
            'films' => $films
        ]);
    }

    /**
    * @Route("/film/{id}", name ="film_show")
    */
    public function show($id) {
        $repo = $this->getDoctrine()->getRepository(Film::class);

        $film = $repo->find($id);

        return $this->render('film/show.html.twig', [
            'film' => $film,
        ]);
    }

}
