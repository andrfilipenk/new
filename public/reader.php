<?php
$it = new RecursiveTreeIterator(new RecursiveDirectoryIterator("../app/", RecursiveDirectoryIterator::SKIP_DOTS));

$output = '';
foreach($it as $path => $bla) {
  if (is_file($path)) {
    $info = new SplFileInfo($path);
    if ($info->getExtension() === 'php') {
      if (!in_array($info->getBasename(), ['bootstrap.php', 'config.php', 'index.php'])) {
        $content = file_get_contents($info->getPathname());
        echo htmlentities($content);
        echo "<br><br>";
        #$content = str_replace(["\r\n", "\t"],"",$content);
        #$output .= $content . "<br><br>";

        #var_dump($info->getPathname());

      }
    }
  }
}

echo $output;