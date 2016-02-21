<?php

namespace AnsibleWrapper;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A wrapper class around the Ansible binary.
 *
 * A AnsibleWrapper object contains the necessary context to run Ansible commands such
 * as the path to the Ansible binary and environment variables. It also provides
 * helper methods to run Ansible commands as set up the connection to the GIT_SSH
 * wrapper script.
 */
class AnsibleWrapper
{
    /**
     * Symfony event dispatcher object used by this library to dispatch events.
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Path to the Ansible binary.
     *
     * @var string
     */
    protected $ansibleBinary;

    /**
     * Environment variables defined in the scope of the Ansible command.
     *
     * @var array
     */
    protected $env = array();

    /**
     * The timeout of the Ansible command in seconds, defaults to 60.
     *
     * @var int
     */
    protected $timeout = 60;

    /**
     * An array of options passed to the proc_open() function.
     *
     * @var array
     */
    protected $procOptions = array();

    /**
     * @var \AnsibleWrapper\Event\AnsibleOutputListenerInterface
     */
    protected $streamListener;

    /**
     * Constructs a AnsibleWrapper object.
     *
     * @param string|null $ansibleBinary
     *   The path to the Ansible binary. Defaults to null, which uses Symfony's
     *   ExecutableFinder to resolve it automatically.
     *
     * @throws \AnsibleWrapper\AnsibleException
     *   Throws an exception if the path to the Ansible binary couldn't be resolved
     *   by the ExecutableFinder class.
     */
    public function __construct($ansibleBinary = null)
    {
        if (null === $ansibleBinary) {
            // @codeCoverageIgnoreStart
            $finder = new ExecutableFinder();
            $ansibleBinary = $finder->find('ansible-playbook');
            if (!$ansibleBinary) {
                throw new AnsibleException('Unable to find the Ansible executable.');
            }
            // @codeCoverageIgnoreEnd
        }

        $this->setAnsibleBinary($ansibleBinary);
    }

    /**
     * Gets the dispatcher used by this library to dispatch events.
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getDispatcher()
    {
        if (!isset($this->dispatcher)) {
            $this->dispatcher = new EventDispatcher();
        }
        return $this->dispatcher;
    }

    /**
     * Sets the dispatcher used by this library to dispatch events.
     *
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     *   The Symfony event dispatcher object.
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * Sets the path to the Ansible binary.
     *
     * @param string $ansibleBinary
     *   Path to the Ansible binary.
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function setAnsibleBinary($ansibleBinary)
    {
        $this->ansibleBinary = $ansibleBinary;
        return $this;
    }

    /**
     * Returns the path to the Ansible binary.
     *
     * @return string
     */
    public function getAnsibleBinary()
    {
        return $this->ansibleBinary;
    }

    /**
     * Sets an environment variable that is defined only in the scope of the Ansible
     * command.
     *
     * @param string $var
     *   The name of the environment variable, e.g. "HOME", "GIT_SSH".
     * @param mixed $default
     *   The value of the environment variable is not set, defaults to null.
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function setEnvVar($var, $value)
    {
        $this->env[$var] = $value;
        return $this;
    }

    /**
     * Unsets an environment variable that is defined only in the scope of the
     * Ansible command.
     *
     * @param string $var
     *   The name of the environment variable, e.g. "HOME", "GIT_SSH".
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function unsetEnvVar($var)
    {
        unset($this->env[$var]);
        return $this;
    }

    /**
     * Returns an environment variable that is defined only in the scope of the
     * Ansible command.
     *
     * @param string $var
     *   The name of the environment variable, e.g. "HOME", "GIT_SSH".
     * @param mixed $default
     *   The value returned if the environment variable is not set, defaults to
     *   null.
     *
     * @return mixed
     */
    public function getEnvVar($var, $default = null)
    {
        return isset($this->env[$var]) ? $this->env[$var] : $default;
    }

    /**
     * Returns the associative array of environment variables that are defined
     * only in the scope of the Ansible command.
     *
     * @return array
     */
    public function getEnvVars()
    {
        return $this->env;
    }

    /**
     * Sets the timeout of the Ansible command.
     *
     * @param int $timeout
     *   The timeout in seconds.
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;
        return $this;
    }

    /**
     * Gets the timeout of the Ansible command.
     *
     * @return int
     *   The timeout in seconds.
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Sets the options passed to proc_open() when executing the Ansible command.
     *
     * @param array $timeout
     *   The options passed to proc_open().
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function setProcOptions(array $options)
    {
        $this->procOptions = $options;
        return $this;
    }

    /**
     * Gets the options passed to proc_open() when executing the Ansible command.
     *
     * @return array
     */
    public function getProcOptions()
    {
        return $this->procOptions;
    }

    /**
     * Set an alternate private key used to connect to servers.
     *
     * This method sets the PRIVATE_KEY_FILE environment variable used by
     * `ansible-playbook`
     *
     * @param string $privateKey
     *   Path to the private key.
     * @param string|null $wrapper
     *   Path the the GIT_SSH wrapper script, defaults to null which uses the
     *   script included with this library.
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     *
     * @throws \AnsibleWrapper\AnsibleException
     *   Thrown when any of the paths cannot be resolved.
     */
    public function setPrivateKey($privateKey, $port = 22, $wrapper = null)
    {
        if (null === $wrapper) {
            $wrapper = __DIR__ . '/../../bin/ansible-ssh-wrapper.sh';
        }
        if (!$wrapperPath = realpath($wrapper)) {
            throw new AnsibleException('Path to GIT_SSH wrapper script could not be resolved: ' . $wrapper);
        }
        if (!$privateKeyPath = realpath($privateKey)) {
            throw new AnsibleException('Path private key could not be resolved: ' . $privateKey);
        }

        return $this
            ->setEnvVar('PRIVATE_KEY_FILE', $privateKeyPath)
        ;
    }

    /**
     * Unsets the private key by removing the appropriate environment variables.
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function unsetPrivateKey()
    {
        return $this
            ->unsetEnvVar('PRIVATE_KEY_FILE')
        ;
    }

    /**
     * Adds output listener.
     *
     * @param \AnsibleWrapper\Event\AnsibleOutputListenerInterface $listener
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function addOutputListener(Event\AnsibleOutputListenerInterface $listener)
    {
        $this
            ->getDispatcher()
            ->addListener(Event\AnsibleEvents::GIT_OUTPUT, array($listener, 'handleOutput'))
        ;
        return $this;
    }

    /**
     * Adds logger listener listener.
     *
     * @param Event\AnsibleLoggerListener $listener
     *
     * @return AnsibleWrapper
     */
    public function addLoggerListener(Event\AnsibleLoggerListener $listener)
    {
        $this
            ->getDispatcher()
            ->addSubscriber($listener)
        ;
        return $this;
    }

    /**
     * Removes an output listener.
     *
     * @param \AnsibleWrapper\Event\AnsibleOutputListenerInterface $listener
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function removeOutputListener(Event\AnsibleOutputListenerInterface $listener)
    {
        $this
            ->getDispatcher()
            ->removeListener(Event\AnsibleEvents::GIT_OUTPUT, array($listener, 'handleOutput'))
        ;
        return $this;
    }

    /**
     * Set whether or not to stream real-time output to STDOUT and STDERR.
     *
     * @param boolean $streamOutput
     *
     * @return \AnsibleWrapper\AnsibleWrapper
     */
    public function streamOutput($streamOutput = true)
    {
        if ($streamOutput && !isset($this->streamListener)) {
            $this->streamListener = new Event\AnsibleOutputStreamListener();
            $this->addOutputListener($this->streamListener);
        }

        if (!$streamOutput && isset($this->streamListener)) {
            $this->removeOutputListener($this->streamListener);
            unset($this->streamListener);
        }

        return $this;
    }

//    /**
//     * Returns an object that interacts with a working copy.
//     *
//     * @param string $directory
//     *   Path to the directory containing the working copy.
//     *
//     * @return AnsibleWorkingCopy
//     */
//    public function workingCopy($directory)
//    {
//        return new AnsibleWorkingCopy($this, $directory);
//    }

    /**
     * Returns the version of the installed Ansible client.
     *
     * @return string
     *
     * @throws \AnsibleWrapper\AnsibleException
     */
    public function version()
    {
        return $this->ansible('--version');
    }

//    /**
//     * Parses name of the repository from the path.
//     *
//     * For example, passing the "ansible@ansiblehub.com:cpliakas/ansible-wrapper.ansible"
//     * repository would return "ansible-wrapper".
//     *
//     * @param string $repository
//     *   The repository URL.
//     *
//     * @return string
//     */
//    public static function parseRepositoryName($repository)
//    {
//        $scheme = parse_url($repository, PHP_URL_SCHEME);
//
//        if (null === $scheme) {
//            $parts = explode('/', $repository);
//            $path = end($parts);
//        } else {
//            $strpos = strpos($repository, ':');
//            $path = substr($repository, $strpos + 1);
//        }
//
//        return basename($path, '.ansible');
//    }

//    /**
//     * Executes a `ansible init` command.
//     *
//     * Create an empty ansible repository or reinitialize an existing one.
//     *
//     * @param string $directory
//     *   The directory being initialized.
//     * @param array $options
//     *   (optional) An associative array of command line options.
//     *
//     * @return \AnsibleWrapper\AnsibleWorkingCopy
//     *
//     * @throws \AnsibleWrapper\AnsibleException
//     *
//     * @see AnsibleWorkingCopy::cloneRepository()
//     *
//     * @ingroup commands
//     */
//    public function init($directory, array $options = array())
//    {
//        $ansible = $this->workingCopy($directory);
//        $ansible->init($options);
//        $ansible->setCloned(true);
//        return $ansible;
//    }

//    /**
//     * Executes a `ansible clone` command and returns a working copy object.
//     *
//     * Clone a repository into a new directory. Use AnsibleWorkingCopy::clone()
//     * instead for more readable code.
//     *
//     * @param string $repository
//     *   The Ansible URL of the repository being cloned.
//     * @param string $directory
//     *   The directory that the repository will be cloned into. If null is
//     *   passed, the directory will automatically be generated from the URL via
//     *   the AnsibleWrapper::parseRepositoryName() method.
//     * @param array $options
//     *   (optional) An associative array of command line options.
//     *
//     * @return \AnsibleWrapper\AnsibleWorkingCopy
//     *
//     * @throws \AnsibleWrapper\AnsibleException
//     *
//     * @see AnsibleWorkingCopy::cloneRepository()
//     *
//     * @ingroup commands
//     */
//    public function cloneRepository($repository, $directory = null, array $options = array())
//    {
//        if (null === $directory) {
//            $directory = self::parseRepositoryName($repository);
//        }
//        $ansible = $this->workingCopy($directory);
//        $ansible->clone($repository, $options);
//        $ansible->setCloned(true);
//        return $ansible;
//    }

    /**
     * Runs an arbitrary Ansible command.
     *
     * The command is simply a raw command line entry for everything after the
     * Ansible binary. For example, a `ansible config -l` command would be passed as
     * `config -l` via the first argument of this method.
     *
     * Note that no events are thrown by this method.
     *
     * @param string $commandLine
     *   The raw command containing the Ansible options and arguments. The Ansible
     *   binary should not be in the command, for example `ansible config -l` would
     *   translate to "config -l".
     * @param string|null $cwd
     *   The working directory of the Ansible process. Defaults to null which uses
     *   the current working directory of the PHP process.
     *
     * @return string
     *   The STDOUT returned by the Ansible command.
     *
     * @throws \AnsibleWrapper\AnsibleException
     *
     * @see AnsibleWrapper::run()
     */
    public function ansible($commandLine, $cwd = null)
    {
        $command = AnsibleCommand::getInstance($commandLine);
        $command->setDirectory($cwd);
        return $this->run($command);
    }

    /**
     * Runs a Ansible command.
     *
     * @param \AnsibleWrapper\AnsibleCommand $command
     *   The Ansible command being executed.
     * @param string|null $cwd
     *   Explicitly specify the working directory of the Ansible process. Defaults
     *   to null which automatically sets the working directory based on the
     *   command being executed relative to the working copy.
     *
     * @return string
     *   The STDOUT returned by the Ansible command.
     *
     * @throws \AnsibleWrapper\AnsibleException
     *
     * @see Process
     */
    public function run(AnsibleCommand $command, $cwd = null)
    {
        $wrapper = $this;
        $process = new AnsibleProcess($this, $command, $cwd);
        $process->run(function ($type, $buffer) use ($wrapper, $process, $command) {
            $event = new Event\AnsibleOutputEvent($wrapper, $process, $command, $type, $buffer);
            $wrapper->getDispatcher()->dispatch(Event\AnsibleEvents::GIT_OUTPUT, $event);
        });
        return $command->notBypassed() ? $process->getOutput() : '';
    }

//    /**
//     * Hackish, allows us to use "clone" as a method name.
//     *
//     * $throws \BadMethodCallException
//     * @throws \AnsibleWrapper\AnsibleException
//     */
//    public function __call($method, $args)
//    {
//        if ('clone' == $method) {
//            return call_user_func_array(array($this, 'cloneRepository'), $args);
//        } else {
//            $class = get_called_class();
//            $message = "Call to undefined method $class::$method()";
//            throw new \BadMethodCallException($message);
//        }
//    }
}
