<?php

declare(strict_types=1);

namespace App\Tests;

use App\Security\User;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use App\Service\BillingClient;

abstract class AbstractTest extends WebTestCase
{

    protected static ?KernelBrowser $client = null;
    protected static User $user;

    protected static array $apiCoursesInfo = [
        'math' => [ 'id' => 0, 'code' => 'math', 'type' => 0, 'cost' => 50, 'name' => 'Алгебра', 'duration' => null, ],
        'db' => [ 'id' => 1, 'code' => 'db', 'type' => 0, 'cost' => 100, 'name' => 'Базы данных', 'duration' => null, ],
    ];

    protected static function getClient(bool $isShouldLogin = false, array $roles = ['ROLE_SUPER_ADMIN', 'ROLE_USER'], bool $reinitialize = false, array $options = [], array $server = [])
    {
        if (!static::$client || $reinitialize) {
            static::$client = static::createClient($options, $server);
            static::$client->disableReboot();

            static::$user = (new User())
                ->setEmail('super_admin@email.com')
                ->setPassword('plain_password')
                ->setRoles($roles)
                ->setBalance(100.0)
            ;
        }

        // core is loaded (for tests without calling of getClient(true))
        static::$client->getKernel()->boot();

        if ($isShouldLogin) {
            static::$client->loginUser(static::$user);
        }

        return static::$client;
    }

    protected function setUp(): void
    {
        static::getClient();
        self::bootKernel();

        $this->loadFixtures($this->getFixtures());
    }

    final protected function tearDown(): void
    {
        parent::tearDown();
//        // Purge all the fixtures data when the tests are finished
//        $purger = new ORMPurger($this->entityManager);
//        // Purger mode 2 truncates, resetting autoincrements
//        $purger->setPurgeMode(2);
//        $purger->purge();
//        $container = static::getContainer();

        static::$client = null;
    }

    /**
     * Shortcut
     */
    protected static function getEntityManager(): EntityManager
    {
        return static::$container->get('doctrine')->getManager();
    }

    /**
     * List of fixtures for certain test
     */
    protected function getFixtures(): array
    {
        return [];
    }

    /**
     * Load fixtures before test
     */
    protected function loadFixtures(array $fixtures = []): void
    {
        $loader = new Loader();

        foreach ($fixtures as $fixture) {
            if (!\is_object($fixture)) {
                $fixture = new $fixture();
            }

            if ($fixture instanceof ContainerAwareInterface) {
                $fixture->setContainer(static::$container);
            }

            $loader->addFixture($fixture);
        }

        $em = static::getEntityManager();
        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function assertResponseOk(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isOk', $message, $type);
    }

    public function assertResponseRedirect(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isRedirect', $message, $type);
    }

    public function assertResponseNotFound(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isNotFound', $message, $type);
    }

    public function assertResponseForbidden(?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, 'isForbidden', $message, $type);
    }

    public function assertResponseCode(int $expectedCode, ?Response $response = null, ?string $message = null, string $type = 'text/html')
    {
        $this->failOnResponseStatusCheck($response, $expectedCode, $message, $type);
    }
    /**
     * @param Response $response
     * @param string   $type
     *
     * @return string
     */
    public function guessErrorMessageFromResponse(Response $response, string $type = 'text/html')
    {
        try {
            $crawler = new Crawler();
            $crawler->addContent($response->getContent(), $type);

            if (!\count($crawler->filter('title'))) {
                $add = '';
                $content = $response->getContent();

                if ('application/json' === $response->headers->get('Content-Type')) {
                    $data = json_decode($content);
                    if ($data) {
                        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        $add = ' FORMATTED';
                    }
                }
                $title = '[' . $response->getStatusCode() . ']' . $add . ' - ' . $content;
            } else {
                $title = $crawler->filter('title')->text();
            }
        } catch (\Exception $e) {
            $title = $e->getMessage();
        }

        return trim($title);
    }

    private function failOnResponseStatusCheck(
        Response $response = null,
        $func = null,
        ?string $message = null,
        string $type = 'text/html'
    ): void
    {
        if (null === $func) {
            $func = 'isOk';
        }

        if (null === $response && self::$client) {
            $response = self::$client->getResponse();
        }

        try {
            if (\is_int($func)) {
                $this->assertEquals($func, $response->getStatusCode());
            } else {
                $this->assertTrue($response->{$func}());
            }

            return;
        } catch (\Exception $e) {
            // nothing to do
        }

        $err = $this->guessErrorMessageFromResponse($response, $type);
        if ($message) {
            $message = rtrim($message, '.') . ". ";
        }

        if (is_int($func)) {
            $template = "Failed asserting Response status code %s equals %s.";
        } else {
            $template = "Failed asserting that Response[%s] %s.";
            $func = preg_replace('#([a-z])([A-Z])#', '$1 $2', $func);
        }

        $message .= sprintf($template, $response->getStatusCode(), $func, $err);

        $max_length = 100;
        if (mb_strlen($err, 'utf-8') < $max_length) {
            $message .= " " . $this->makeErrorOneLine($err);
        } else {
            $message .= " " . $this->makeErrorOneLine(mb_substr($err, 0, $max_length, 'utf-8') . '...');
            $message .= "\n\n" . $err;
        }

        $this->fail($message);
    }

    private function makeErrorOneLine($text)
    {
        return preg_replace('#[\n\r]+#', ' ', $text);
    }
}
