<?php
namespace App\Controller;

use App\Entity\Movies;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use App\Service\MovieApi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    use DataTablesTrait;

    public function __construct(MovieApi $movieApi)
    {
        $this->movieService = $movieApi;
    }

    /**
     * @Route("/api-authenticate", name="api_authenticate")
     */
    public function apiAuthenticate()
    {
        $authUrl = $this->movieService->authenticate();

        return $this->redirect($authUrl);
    }

    /**
     * @Route("/authenticate-movie", name="authenticate_movie")
     */
    public function authenticateMovie(Request $request)
    {
        $session = $this->movieService->createApiSession($request->get("request_token"));

        dump($session); exit;
    }

    /**
     * @Route("/datatable", name="data_table")
     */
    public function dataTable(Request $request)
    {
        $page = 1;
        if($request->get("page", null)) {
            $page = $request->get("page");
        }

        $movies = $this->movieService->getMovies($page);
        $table = $this->createDataTable()
        ->add('id', TextColumn::class)
        ->add('title', TextColumn::class)
        ->add('overview', TextColumn::class)
        ->add('vote_count', TextColumn::class)
        ->add('vote_average', TextColumn::class)
        ->add('release_date', DateTimeColumn::class, ["format" => "Y-m-d"])
        ->createAdapter(ArrayAdapter::class,
            $movies['results']
        )
        ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('datatable/datatable.html.twig', ['datatable' => $table]);
    }

    /**
     * @Route("/addMovies/{nr}", name="add_movies")
     */
    public function addMovies($nr)
    {
        $pages = $nr / 20;
        $movies = [];
        $batchSize = 20;

        for($i = 1; $i <= $pages; $i++) {
            sleep(0.5);
            $movies = array_merge($this->movieService->getMovies($i)["results"],$movies);
        }

        $em = $this->getDoctrine()->getManager();

        foreach($movies as $key => $movie) {
            $mov = new Movies;
            $mov->setTitle($movie["title"]);
            $mov->setOverview($movie["overview"]);
            $mov->setVoteCount($movie["vote_count"]);
            $mov->setVoteAverage($movie["vote_average"]);
            $mov->setReleaseDate($movie["release_date"]);
            $em->persist($mov);
            if($key % $batchSize == 0) {
                $em->flush();
            }
        }

        $em->flush();
        dump($movies);

        return new Response();
    }

    /**
     * @Route("/list/movies", name="list_movies")
     */
    public function listMovies(Request $request)
    {
        $table = $this->createDataTable()
        ->add('id', TextColumn::class, ["label" => "ID", "className" => "bold"])
        ->add('title', TextColumn::class, ["field" =>"movies.title","label" => "Title", "render" => "<strong>%s</strong>", "raw" => true])
        ->add('overview', TextColumn::class , ["field" =>"movies.overview"])
        ->add('voteCount', TextColumn::class)
        ->add('voteAverage', TextColumn::class)
        ->add('releaseDate', DateTimeColumn::class, ["format" => "Y-m-d"])
        ->createAdapter(ORMAdapter::class,[
            'entity' => Movies::class
        ])
        ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('datatable/datatable.html.twig', ['datatable' => $table]);
    }

    /**
     * @Route("/list/raw/movies", name="list_raw_movies")
     */
    public function listRawMovies()
    {
        return $this->render('datatable/raw-datatable.html.twig');
    }

    /**
     * @Route("/get/movies", name="get_movies")
     */
    public function getMovies(Request $request)
    {
        $movies = $this->getDoctrine()
        ->getRepository(Movies::class)
        ->createQueryBuilder('e')
        ->getQuery()
        ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

        $response = new Response(json_encode(["data" => $movies]));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}