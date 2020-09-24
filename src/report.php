<?php

namespace Edge\QA;

use DOMDocument;
use XSLTProcessor;
use Exception;
use Twig\Loader\Filesystem;
use Twig\Environment;

function twigToHtml($template, array $params, $outputFile)
{
    $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../app/report');
    $twig = new \Twig\Environment($loader);
    $html = $twig->render($template, $params);
    file_put_contents($outputFile, $html);
}

function xmlToHtml(array $xmlDocuments, $style, $outputFile, array $params = [])
{
    if (!$xmlDocuments) {
        return;
    }

    convertPhpErrorsToExceptions();
    try {
        $rootXml = array_shift($xmlDocuments);
        $xml = new DOMDocument();
        $xml->load($rootXml);

        foreach ($xmlDocuments as $file) {
            $anotherXml = new DOMDocument();
            $anotherXml->load($file);
            $xml->documentElement->appendChild($xml->importNode($anotherXml->documentElement, true));
        }

        $xsl = new DOMDocument();
        $xsl->load($style);

        $xslt = new XSLTProcessor();
        foreach ($params as $param => $value) {
            $xslt->setParameter('', $param, $value);
        }
        $xslt->importStylesheet($xsl);
        $xslt->transformToDoc($xml)->saveHTMLFile($outputFile);
    } catch (Exception $e) {
        file_put_contents($outputFile, $e->getMessage());
    }
}

function xmlXpath($xmlFile, $xpathQuery)
{
    convertPhpErrorsToExceptions();
    try {
        $xml = simplexml_load_file($xmlFile);
        return [false, $xml->xpath($xpathQuery)];
    } catch (Exception $e) {
        return [true, $e->getMessage()];
    }
}

function convertPhpErrorsToExceptions()
{
    static $isNotLoaded = true;
    if ($isNotLoaded) {
        set_error_handler('Edge\QA\phpErrorToException');
        $isNotLoaded = false;
    }
}

function phpErrorToException($severity, $message, $filename, $lineno)
{
    if (error_reporting() & $severity) {
        throw new \ErrorException($message, 0, $severity, $filename, $lineno);
    }
}
