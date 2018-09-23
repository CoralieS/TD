<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Movie;
use AppBundle\Entity\User;
use AppBundle\Repository\MovieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * User controller.
 *
 */
class UserController extends Controller
{
    /**
     * Lists all user entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('AppBundle:User')->findAll();

        return $this->render('user/index.html.twig', array(
            'users' => $users,
        ));
    }

    /**
     * Creates a new user entity.
     *
     */
    public function newAction(Request $request)
    {
        $user = new User();
        $form = $this->createForm('AppBundle\Form\UserType', $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('user_show', array('id' => $user->getId()));
        }

        return $this->render('user/new.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a user entity.
     *
     */
    public function showAction(User $user)
    {
        $deleteForm = $this->createDeleteForm($user);

        return $this->render('user/show.html.twig', array(
            'user' => $user,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing user entity.
     *
     */
    public function editAction(Request $request, User $user)
    {
        $deleteForm = $this->createDeleteForm($user);
        $editForm = $this->createForm('AppBundle\Form\UserType', $user);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_edit', array('id' => $user->getId()));
        }

        return $this->render('user/edit.html.twig', array(
            'user' => $user,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a user entity.
     *
     */
    public function deleteAction(Request $request, User $user)
    {
        $form = $this->createDeleteForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * Creates a form to delete a user entity.
     *
     * @param User $user The user entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(User $user)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('user_delete', array('id' => $user->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    /**
     * Save movie choice
     * @param $userId
     * @return JsonResponse
     */
    public function saveMovie($userId)
    {
        // api infos
        $apiId  = 'tt3896198';
        $apiKey = '9b5361b9';

        // prepare datas to send to api and decode datas from api

        $datas = $this->getDatas(Request::class, $apiId, $apiKey);

        // check that user has voted less than 3 times

        $numberVotes = $this->getNumberOfVotes($userId);

        return $datas;

    }

    /**
     * Get number of votes
     * @param $userId
     * @return JsonResponse
     */
    public function getNumberOfVotes($userId)
    {
        $movieRepository = $this->getDoctrine()->getRepository(MovieRepository::class);
        $userMovies      = $movieRepository->findBy($userId);
        $userVotes       = count($userMovies);
        $limit = 3;
        $remainingVotes = $limit - $userVotes;

        if ($userVotes < 3) {
            return new JsonResponse("You can still vote".$remainingVotes."time(s)", 200);
        }
    }

    /**
     * Get datas to send and save movie
     * @param $apiId
     * @param $apiKey
     * @return JsonResponse
     */
    public function getDatas(Request $request, $apiId, $apiKey)
    {
        $userId = $this->getUser();

        // obtain and check datas (title and image)
        $data = json_decode($request->getContent(), true);
        if (!isset($data['title']) || empty($data['title'])) {
            return new JsonResponse(['error'=>'no title'], 422);
        }
        if (!isset($data['poster']) || empty($data['poster'])) {
            return new JsonResponse(['error'=>'no poster'], 422);
        }

        $title     = urlencode($data['title']);
        $url       = file_get_contents("http://www.omdbapi.com/?i=".$apiId."&apikey=".$apiKey."?t=".$title);
        $movieData = json_decode($url, true);

        $result = [
            'movieTitle'  => $movieData['Title'],
            'moviePoster' => $movieData['Poster'],
            'movieApiId'  => $movieData['imdbID'],
        ];

        $movie = new Movie();
        $movie->setTitle($result['movieTitle']);
        $movie->setImage($result['moviePoster']);
        $movie->addMovie($userId);
        $em = $this->getDoctrine()->getManager();
        $em->persist($movie);
        $em->flush();


    }


    /**
     * Delete movie
     * @param $movie_id
     * @return JsonResponse
     */
    public function deleteMovie($movie_id)
    {
        $movie = $this->getDoctrine()->getRepository(Movie::class)->findBy($movie_id);
        $em = $this->getDoctrine()->getManager();
        $em->remove($movie['id']);
        $em->flush();
        return new JsonResponse(NULL);
    }

    /**
     * Get movies per user
     * @param $user_id
     * @return JsonResponse
     */
    public function getMoviesPerUser($user_id)
    {
        $movieRepository = $this->getDoctrine()->getRepository(Movie::class);
        $movies = $movieRepository->findBy($user_id);
        $response = [];
        foreach ($movies as $movie) {
            $response[] = $movie[$user_id];
        }
        return new JsonResponse($response);
    }
}