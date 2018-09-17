<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use JiraRestApi\Project\ProjectService;
use JiraRestApi\JiraException;
use Github\Client;

class DefaultController extends Controller
{
    public $projects = ['RG', 'RK', 'TAK', 'MUS'];

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $res = [];
        $res['jiraVersions'] = $this->jira();
        $res['github'] = $this->github();

        return new JsonResponse($res);

        // return $this->render('default/index.html.twig', [
        //     'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        // ]);
    }

    public function github()
    {
        $githubManager = $this->container->get('github.client');
        $openPullRequests = $githubManager->api('pull_request')->all('drdk', 'drupal-dr-dk', array('state' => 'all'));

        return $openPullRequests;
    }

    public function jira()
    {
        $proj = new ProjectService();
        $respons = [];
        try {
            foreach ($this->projects as $project) {
                foreach ($proj->get($project)->versions as $version) {
                    $respons[$project]['full'][] = $version;
                    $data = ['name' => $version->name];
                    if (isset($version->releaseDate)) {
                        $data['release-date'] = $version->releaseDate;
                    }

                    if (strpos($version->name, '--') !== false) {
                        if ($version->released) {
                            $respons[$project]['released'] = $data;
                        } else {
                            $respons[$project]['unreleased'] = $data;
                        }
                    } else {
                        $respons[$project]['++'][] = $version->name;
                    }
                }
                // sort($respons[$project]['++']);
                // sort($respons[$project]['--']);
            }

            return $respons;

            // var_dump($p);
        } catch (JiraException $e) {
            echo 'Error Occured! '.$e->getMessage();
        }
    }
}
