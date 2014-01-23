<?php

class LatexRenderer {

  private $port = null, $proc = null;

  public function __construct() {
    $this->startServer();
  }

  public function __destruct() {
    $this->killServer();
  }

  static private $instance = null;

  // mode is "inline" or "display"
  public static function render($src, $mode = "inline") {
    if (self::$instance === null) {
      self::$instance = new LatexRenderer();
    }
    if ($mode === "display") {
      $src = "\\displaystyle\{$src\}";
    }
    return self::$instance->callRender($src);
  }

  private function callRender($src) {
    $sol = $this->doRender($src);
    if ($sol === false) {
      $this->killServer();
      $this->startServer();
      sleep(5);
      $sol = $this->doRender($src);
      if ($sol === false) {
        echo "svgtex unresponsive!";
        return "problem with backend.";
      }
    }
    return $sol;
  }

  private function doRender($src) {
    $ch = curl_init();

    $src = "type=tex&q=" . urlencode($src) . "&width=";
    curl_setopt($ch, CURLOPT_URL, "http://localhost:$this->port/tex/");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $src);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $sol = curl_exec ($ch);
    curl_close ($ch);

    return $sol;
  }

  private function killServer() {
    $status = proc_get_status($this->proc);
    if($status['running'] == true) { //process ran too long, kill it
      //get the parent pid of the process we want to kill
      $ppid = $status['pid'];
      //use ps to get all the children of this process, and kill them
      $pids = preg_split('/\s+/', shell_exec("ps -o pid --no-heading --ppid $ppid"));
      foreach($pids as $pid) {
        if(is_numeric($pid)) {
          posix_kill($pid, 9); //9 is the SIGKILL signal
        }
      }
      proc_close($this->proc);
    }
  }

  private function startServer() {
    $pipes = null;
    $this->port = 16536 + rand() % 10000;
    $this->proc = proc_open(
      "phantomjs main.js --port $this->port",
      array(
        0 => array("file", "/dev/null", "r"),
        1 => array("file", "/dev/null", "w"),
        2 => array("file", "/dev/null", "w")
      ),
      $pipes,
      dirname(__FILE__) . '/../svgtex' // working dir is where main.js is
    );
    if ($this->proc === false) {
      throw new Exception("failed to open latex process.");
    }
    sleep(2);
    $status = proc_get_status($this->proc);
  }

};

// LatexRenderer::render('warmup');

