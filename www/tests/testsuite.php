<?php
header('Content-Type:text/plain');

function_exists('curl_init') || die('cUrl extension missing');

$basepaths = [
    'http://caddy',
    'http://httpd',
    'http://nginx'
];

$fixtures = [
    [
        'name' => 'Moodle-like routing URL i.e. not an index file which requires proper PATH_INFO support',
        'url' => '/lib/javascript.php/1599824490/lib/requirejs/require.min.js',
        'PATH_INFO' => '/1599824490/lib/requirejs/require.min.js',
        'QUERY_STRING' => ''
    ],
    [
        'name' => 'Index file w/ PATH_INFO',
        'url' => '/index.php/foo',
        'PATH_INFO' => '/foo',
        'QUERY_STRING' => ''
    ],
    [
        'name' => 'Candidate index file w/ PATH_INFO and QUERY_STRING',
        'url' => '/index.php/foo?a=1&b=2',
        'PATH_INFO' => '/foo',
        'QUERY_STRING' => 'a=1&b=2'
    ],
    [
        'name' => 'Non existing folder but with index.php fallback',
        'url' => '/foo',
        'PATH_INFO' => '<false>', // <null>=no index.php fallback, <false>=not defined.
        'QUERY_STRING' => ''     // <null>=no index.php fallback, <false>=not defined.
    ],
    [
        'name' => 'Non existing PHP file but with index.php fallback',
        'url' => '/foo.php/foo',
        'PATH_INFO' => '<false>', // <null>=no index.php fallback, <false>=not defined.
        'QUERY_STRING' => ''     // <null>=no index.php fallback, <false>=not defined.
    ],
    [
        'name' => 'Not an index file including UTF-8 char into the candidate PATH_INFO',
        'url' => '/file.php/filename_UTF8_en+coded_それが動作するはず.png',
        'PATH_INFO' => '/filename_UTF8_en+coded_それが動作するはず.png',
        'QUERY_STRING' => ''
    ],
    [
        'name' => 'PATH_INFO with whitespaces',
        'url' => '/index.php/some%20%20whitespaces',
        'PATH_INFO' => '/some  whitespaces',
        'QUERY_STRING' => ''
    ],
    // [
    //     'name' => '',
    //     'url' => '',
    //     'PATH_INFO' => '',
    //     'QUERY_STRING' => ''
    // ],
];

function getURIOutput($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

function getServerVariable($output, $srvVar) {
    // Sanity check: the resource has been executed by a PHP interpreter?
    if (substr($output, 0, 5 ) !== "Array") {
      return null;
    }

    if (preg_match('/\[(?<srvvar>' . $srvVar .')\] => (?<value>.*)\n/u', $output, $matches)) {
        return $matches['value'];
    }

    // The Server Variable is not there!
    return false;
}

foreach ($basepaths as $basepath) {
    echo "Testing '${basepath}'...\n";
    foreach ($fixtures as $fixture) {
        $url = $basepath . $fixture['url'];
        echo " => Contacting URL: '${url}'...\n";
        $output = getURIOutput($url);
        foreach ([
                    'PATH_INFO',
                    'QUERY_STRING'
                ] as $srvVar) {
            $value = getServerVariable($output, $srvVar);
            $actualValue = $value;
            if (is_bool($value)) {
                $actualValue = $value ? '<true>' : '<false>';  
            } else if ($value === null) {
                $actualValue = '<null>';
            }
            if ($fixture[$srvVar] !== $actualValue) {
                echo "     Test '${fixture['name']}' failed:\n";
                echo "     => KO ${srvVar}. Expected: '${fixture[$srvVar]}' vs Actual: '${actualValue}'.\n";
            }
        }
        echo "    Done.\n";
    }
    echo "\n";
}
