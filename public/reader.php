<?php
$it = new RecursiveTreeIterator(new RecursiveDirectoryIterator("../app/", RecursiveDirectoryIterator::SKIP_DOTS));


$content = file_get_contents(__DIR__ . '/index.php');
echo htmlentities($content);
echo "<br><br>";

$output = '';
$templates = [];
foreach($it as $path => $bla) {
  if (is_file($path)) {
    $info = new SplFileInfo($path);
    if (!in_array($info->getExtension(), ['php', 'phtml'])) {
      continue;
    }
    if ($info->getExtension() === 'php') {
      if (!in_array($info->getBasename(), ['tester.php'])) {
        $content = file_get_contents($info->getPathname());
        echo htmlentities($content);
        echo "<br><br>";
        #$content = str_replace(["\r\n", "\t"],"",$content);
        #$output .= $content . "<br><br>";

        #var_dump($info->getPathname());

      }
    }
    if ($info->getExtension() === 'phtml') {
      $templates[] = $info->getPathname();

    }
  }
}

echo $output;
echo implode("<br><br>", $templates);