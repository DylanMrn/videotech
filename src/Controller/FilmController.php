<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Film;
use App\Entity\Category;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\FilmRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

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
    public function home(FilmRepository $repo, UserRepository $user, Request $request, EntityManagerInterface $manager) {
        $films = $repo->findAll();
        $total = count($films);

        //$role = $user->findBy($user[0]);
        $user = $this->getUser();
        dump($user);

        $form = $this->createFormBuilder($user)
                     ->add('change', SubmitType::class)
                     ->getForm();

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            if($user->getRoles() == "['ROLE_USER']"){
                $user->setRoles(['ROLE_ADMIN']);
            } else {
                $user->setRoles(['ROLE_USER']);
            }

            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('film/home.html.twig', [
            'title' => 'Page des films',
            'total' => $total,
            'user' => $user,
            'formRole' => $form->createView()
        ]);
    }

    /**
    * @Route("/film/new", name="film_create")
    * @Route("/film/{id}/edit", name="film_edit")
    */
    public function create(FilmRepository $repo, Film $film = null, Request $request, EntityManagerInterface $manager, \Swift_Mailer $mailer){

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
                     ->add('image', FileType::class)    
                     ->getForm();

            $form->handleRequest($request);

            if($form->isSubmitted() && $form->isValid()){
                if(!$film->getId()){
                    $film->setCreatedAt(new \DateTime());

                    $message = (new \Swift_Message('Hello Email'))
                    ->setFrom('noreply@test.fr')
                    ->setTo('noreply@test.fr')
                    ->setBody(
                        $this->renderView(
                           'emails/create.html.twig',
                            ['film' => $film ]
                        ),
                        'text/html'
                    );
                $mailer->send($message);

                }

                $manager->persist($film);
                $manager->flush();

                return $this->redirectToRoute('film_show', ['id'=> $film->getId(), 'category'=>$film->getCategory()->getTitle(), 'title'=>$film->getTitle()]);
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
    public function delete($id, EntityManagerInterface $manager, \Swift_Mailer $mailer) {
        $repo = $this->getDoctrine()->getRepository(Film::class);

        $films = $repo->findAll();
        $total = count($films);

        $repo = $repo->find($id);

        $message = (new \Swift_Message('Hello Email'))
                    ->setFrom('noreply@test.fr')
                    ->setTo('noreply@test.fr')
                    ->setBody(
                        $this->renderView(
                           'emails/delete.html.twig',
                            ['film' => $repo ]
                        ),
                        'text/html'
                    );
        $mailer->send($message);

        $manager->remove($repo);
        dump($repo);

        $manager->flush();

        return $this->redirectToRoute('film');
    }

    /**
    * @Route("/{category}/{title}-{id}", name ="film_show")
    */
    public function show($id, $category, $title) {

        $id = explode("-", $id);
        $total = count($id) -1;

        $repo = $this->getDoctrine()->getRepository(Film::class);
        $film = $repo->find($id[$total]);

        return $this->render('film/show.html.twig', [
            'film' => $film,
        ]);
    }

    /**
    * @Route("/recherche", name ="recherche")
    */
    public function recherche(Request $request) {
        $repo = $this->getDoctrine()->getRepository(Film::class);
        $categorie = $this->getDoctrine()->getRepository(Category::class);
        $recherche = null; $message = "";

        $films = $repo->findAll();
        $categories = $categorie->findAll();

        $form2 = $this->createFormBuilder()
            ->add('title', TextType::class)
            ->add('categorie', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'title'
            ])
            ->getForm();

            $form2->handleRequest($request);

            if($form2->isSubmitted() && $form2->isValid()){
                $title = $request->request->get('form')['title'];
                $categorie = $request->request->get('form')['categorie']; 
                $recherche = $repo->FindByTitle($title, $categorie);
                if(empty($recherche)){
                    $message = "pas de résultats";
                }
            }

        return $this->render('film/recherche.html.twig', [
            'films' => $films,
            'categories' => $categories,
            'formSearch' => $form2->createView(),
            'recherches' => $recherche,
            'message' => $message
        ]);
    }
}
