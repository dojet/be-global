<?php
/*
 * Daemon 后台脚本控制类
 *
 * Daemon使用方法:
 * 直接在后台代码前面加上 Daemon::daemonize();
 * 如果希望多个进程跑，则使用Daemon::runAsMainChildren($count, $options);
 * 如果希望根据任务情况自动调整进程数量，则使用Daemon::runAsAutoPool($max, $options);
 * $options中可控制最大进程数、等待时间等。
 * 工作进程中，可通过长期空闲的时候自己退出，
 * 任务过多的时候向父进程发起HUP信号
 * 来控制合适的工作进程数。
 * 可使用getopt 读取控制台选项
 *
 * @date 2012-09-26
 */
class Daemon {

    private static $workers_count = 0;
    private static $workers_max = 0;
    private static $workers_min = 0;
    private static $pid_file = null;
    private static $info_dir = null;
    private static $main_pid = 0;
    private static $options  = null;

    public function __construct($options){
    }

    static public function daemonize($options = array()){
        global $stdin, $stdout, $stderr;
        global $argv;

        set_time_limit(0);
        $default_options = array(
            'user'          => null,
            'output'        => '/dev/null',
        );
        $options = array_merge($default_options, $options);

        if (php_sapi_name() != "cli"){
            die("only run in command line mode\n");
        }

        if (isset($options['pid'])){
            if (!isset($options['info_dir'])){
                self::$info_dir = "/tmp";
            }
            else {
                self::$info_dir = $options['info_dir'];
            }
            self::$pid_file = self::$info_dir . "/" .__CLASS__ . "_" . substr(basename($argv[0]), 0, -4) . ".pid";
            self::checkPidfile();
        }

        umask(0);

        if (pcntl_fork() != 0){
            exit();
        }

        posix_setsid();

        if (pcntl_fork() != 0){
            exit();
        }

        println('pid: '.posix_getpid());
        chdir("/");

        self::setUser($options['user']) or die("cannot change owner");

        //close file descriptior
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);
        $output = $options['output'];
        $stdin  = fopen("/dev/null", 'r');
        $stdout = fopen($output, 'a');
        $stderr = fopen($output, 'a');

        if (isset($options['pid'])){
            self::createPidfile();
        }
    }

    static public function run($mode='worker', $options=array()){
        if (!isset($options['console']) || $options['console']){
            $options = self::getopt($options);
        }

        if ($mode == 'pool'){
            self::daemonize($options);

            $workers_max = isset($options['workers_max']) ? $options['workers_max'] : 5;
            self::runAsAutoPool($workers_max, $options);
        }
        else if($mode = 'worker'){
            self::daemonize($options);

            $workers_count = isset($options['workers_count']) ? $options['workers_count'] : 1;
            self::runAsMainChildren($workers_count, $options);
        }
        else if ($mode == 'daemonize'){
            self::daemonize($options);
        }
    }

    /**
     * runJobs 多进程处理特定任务列表, 主进程等待其处理完毕, 可以指定最大的处理进程数.
     * 方法返回处理成功的任务列表, handler返回true表示成功。
     *
     * @param mixed $jobs
     * @param mixed $handler
     * @param array $options
     * @access public
     * @return void
     */
    public function runJobs($jobs, $handler, $options = array()){
        if (!is_array($jobs) || !is_callable($handler)){
            return false;
        }


        $max = isset($options['max']) ? intval($options['max']) : 0;
        $job_count = count($jobs);
        $workers = 0;
        $i = 0;
        $result = array();
        $mapper = array();

        while(true){
            $job = $jobs[$i];

            $pid = pcntl_fork();
            if ($pid == 0){
                $result = $handler($job);
                if ($result){
                    exit(0);
                }
                exit(1);
            }
            else if ($pid > 0) {
                $mapper[$pid] = $i;
                $i ++;
                $workers++;

                if ($i >= $job_count){
                    break;
                }

                if ($max > 0 && $workers >= $max){
                    if ($pid = pcntl_waitpid(-1, $status)){
                        $workers --;
                        if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0){
                            $result[] = $mapper[$pid];
                        }
                        while($pid = pcntl_waitpid(-1, $status, WNOHANG)){
                            if ($pid == -1){
                                break;
                            }
                            $workers --;
                            if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0){
                                $result[] = $mapper[$pid];
                            }
                        }
                    }
                }
            }
        }

        $status = 0;
        while($workers > 0){
            $pid = pcntl_waitpid(-1, $status);
            if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0){
                $result[] = $mapper[$pid];
            }
            $workers --;
        }
        return $result;
    }

    static public function runAsMainChildren($count=1, $options=array()){
        self::$workers_count = 0;
        $status = 0;

        declare(ticks=1);
        pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler")); // kill all workers if send kill to main process
        pcntl_signal(SIGCHLD, array(__CLASS__, "signalHandler"));

        _log("daemon process is woring now");

        while(true){
            $pid = -1;
            if (self::$workers_count < $count){
                $pid = pcntl_fork();
            }

            if ($pid > 0){
                self::$workers_count ++;
            }
            elseif ($pid == 0){
                if(isset($options['job_handler'])){
                    call_user_func($options['job_handler']);
                }
                return;
            }
            else {
                sleep(1);
            }
        }
        self::mainQuit();
        exit(0);
    }

    static public function runAsAutoPool($workers_max=10, $options=array()){
        $main_pid = posix_getpid();

        self::$workers_max   = $workers_max;
        self::$workers_min   = 1;
        self::$workers_count = 0;
        $status = 0;

        declare(ticks=1);
        pcntl_signal(SIGTERM, array(__CLASS__, "signalHandler")); // kill all workers if send kill to main process
        pcntl_signal(SIGCHLD, array(__CLASS__, "signalHandler")); // if worker die, minus children num
        pcntl_signal(SIGUSR1, array(__CLASS__, "signalHandler")); // if send signal usr1 means busy


        while(true){
            $pid = -1;
            if (self::$workers_count < self::$workers_min){
                $pid = pcntl_fork();
            }

            if ($pid > 0){
                self::$workers_count ++;
            }
            elseif ($pid == 0){
                if(isset($options['job_handler'])){
                    call_user_func($options['job_handler']);
                }
                return;
            }
            else {
                sleep(1);
                if (posix_getpid() != $main_pid){
                    _log("run busy $pid");
                    if(isset($options['job_handler'])){
                        call_user_func($options['job_handler']);
                    }
                    return;
                }
            }
        }
        exit(0);
    }


    // 向deliver进程发送HUP信号
    public static function notifyBusy($pid = 0) {
        $pid = $pid > 0 ? $pid : posix_getppid();
        if ($pid > 1){
            posix_kill($pid, SIGUSR1);
        }
    }


    //信号处理函数， 只在父进程中执行
    static private function signalHandler($signo){
        switch($signo){
            case SIGUSR1: //busy
                if (self::$workers_count < self::$workers_max){
                    $pid = pcntl_fork();
                    if ($pid > 0){
                        self::$workers_count ++;
                    }
                }
                break;

            case SIGCHLD:
                while(($pid=pcntl_waitpid(-1, $status, WNOHANG)) > 0){
                        self::$workers_count --;
                }
                break;
            case SIGTERM:
            case SIGHUP:
            case SIGQUIT:
                self::mainQuit();
                break;
            default:
                return false;
        }
    }

    /**
     * 设置用户ID和组ID
     *
     * @param string $name
     * @return void
     */
    static private function setUser($name){
        $result = false;
        if (empty($name)){
            return true;
        }
        $user = posix_getpwnam($name);
        if ($user) {
            $uid = $user['uid'];
            $gid = $user['gid'];
            $result = posix_setuid($uid);
            posix_setgid($gid);
        }
        return $result;
    }

    public function checkPidfile(){
        if (!file_exists(self::$pid_file)){
            return true;
        }
        $pid = file_get_contents(self::$pid_file);
        $pid = intval($pid);
        if ($pid > 0 && posix_kill($pid, 0)){
            _log("the daemon process is already started");
        }
        else {
            _log("the daemon proces end abnormally, please check pidfile " . self::$pid_file);
        }
        exit(1);
    }

    public function createPidfile(){
        if (!is_dir(self::$info_dir)){
            mkdir(self::$info_dir);
        }
        $fp = fopen(self::$pid_file, 'w') or die("cannot create pid file");
        fwrite($fp, posix_getpid());
        fclose($fp);
        _log("create pid file " . self::$pid_file);
    }

    public function mainQuit(){
        if (file_exists(self::$pid_file)){
            unlink(self::$pid_file);
            _log("delete pid file " . self::$pid_file);
        }
        _log("daemon process exit now");
        posix_kill(0, SIGKILL);
        exit(0);
    }

    public function getopt($default=array()){
        $params = getopt("c:m:u:p::o:d:h"); //child num
        $options = array();
        if(isset($params['h'])){
            self::printHelp();
            exit(0);
        }
        if (isset($params['c'])){
            $options['workers_count'] = intval($params['c']);
        }
        if (isset($params['m'])){
            $options['workers_max'] = intval($params['m']);
        }
        if (isset($params['p'])){
            $options['pid'] = true;
        }
        if (isset($params['d']) && is_dir($params['d'])){
            $options['info_dir'] = $params['d'];
        }
        if (isset($params['u'])){
            $options['user'] = $params['u'];
        }
        if (isset($params['o'])){
            $options['output'] = $params['o'];
        }
        return array_merge($default, $options);
    }

    public function printHelp(){
        global $argv;
        $script = $argv[0];
        echo <<<HELP

Usage:
   php $script [options]
or
   php -f $script -- [options]
    options:
        -c <n>    -- children num
        -m <n>    -- max children num
        -u <user> -- run daemon script as <user>
        -d <dir>  -- info dir
        -p        -- use pid file
        -o <file> -- output info to <file>
        -h        -- print help
HELP;
    }
}

if (!function_exists('_log')){
    function _log($msg){
        printf("%s\t%d\t%d\t%s\n", date("c"), posix_getpid(), posix_getppid(), $msg);
    }
}
