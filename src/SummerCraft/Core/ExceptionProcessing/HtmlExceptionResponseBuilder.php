<?php

namespace SummerCraft\Core\ExceptionProcessing;

class HtmlExceptionResponseBuilder
{
    public static function buildForDbError(string $heading, string $message): string
    {
        return self::buildGeneral('Database error', $heading, $message);
    }

    public static function buildForGeneral(string $heading, string $message): string
    {
        return self::buildGeneral('ERROR', $heading, $message);
    }

    /**
     * @param ExceptionDescription[] $exceptionDescriptions
     * @param int|null $showDebugBacktraceType
     * @return string
     */
    public static function buildForException(
        array $exceptionDescriptions,
        ?int $showDebugBacktraceType
    ): string {
        $result = '[!APP-FAILED!] ';

        foreach ($exceptionDescriptions as $exceptionDescription) {

            $backtraceBlock = '';
            if ($showDebugBacktraceType === ExceptionProcessor::BACKTRACE_TYPE_DEFAULT_SPECIFIC) {

                $backtraceLinesBlock = '';
                foreach ($exceptionDescription->getBacktraceArray() as $key => $line) {
                    $deep = $key + 1;
                    $backtraceLinesBlock .= <<<ENDOFTEXT
                        <tr>
                            <td style="padding: 2px 10px">$deep : {$line}</td>
                        </tr>
                    ENDOFTEXT;
                }

                $backtraceBlock .= <<<ENDOFTEXT

                <p>Backtrace:</p>
                <table>
                    <tr>
                        <th style="padding: 2px 10px">File:Line - Function</th>
                    </tr>
                    <tr>
                        <td style="padding: 2px 10px">0 : {$exceptionDescription->getFileName()}:{$exceptionDescription->getFileLine()}</td>
                    </tr>
                    $backtraceLinesBlock
                </table>

                ENDOFTEXT;

            } elseif ($showDebugBacktraceType === ExceptionProcessor::BACKTRACE_TYPE_DEFAULT_WITH_PARAMETER) {

                $backtraceBlock .= <<<ENDOFTEXT

                <p>Backtrace:</p>
                <pre>
                    {$exceptionDescription->getBacktraceAsString()}
                </pre>

                ENDOFTEXT;
            }

            $result .= <<<ENDOFTEXT
            
            <div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;">
            
            <h4><span>[!APP-FAILED!]</span> {$exceptionDescription->getExceptionTitle()}</h4>
            
            <p>Type: {$exceptionDescription->getExceptionType()}</p>
            <p>Message: <pre>{$exceptionDescription->getMessage()}</pre></p>
            <p>Filename Line: {$exceptionDescription->getFileName()}:{$exceptionDescription->getFileLine()}</p>
            
            $backtraceBlock

            </div>

            ENDOFTEXT;
        }

        return $result;




    }

    private static function buildGeneral(string $errorTitle, string $heading, string $message): string
    {
        return <<<ENDOFTEXT
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="utf-8">
        <title>{$errorTitle}</title>
        <style>
        
        ::selection { background-color: #E13300; color: white; }
        ::-moz-selection { background-color: #E13300; color: white; }
        
        body {
            background-color: #fff;
            margin: 40px;
            font: 13px/20px normal Helvetica, Arial, sans-serif;
            color: #4F5155;
        }
        
        a {
            color: #003399;
            background-color: transparent;
            font-weight: normal;
        }
        
        h1 {
            color: #444;
            background-color: transparent;
            border-bottom: 1px solid #D0D0D0;
            font-size: 19px;
            font-weight: normal;
            margin: 0 0 14px 0;
            padding: 14px 15px 10px 15px;
        }
        
        code {
            font-family: Consolas, Monaco, Courier New, Courier, monospace;
            font-size: 12px;
            background-color: #f9f9f9;
            border: 1px solid #D0D0D0;
            color: #002166;
            display: block;
            margin: 14px 0 14px 0;
            padding: 12px 10px 12px 10px;
        }
        
        #container {
            margin: 10px;
            border: 1px solid #D0D0D0;
            box-shadow: 0 0 8px #D0D0D0;
        }
        
        p {
            margin: 12px 15px 12px 15px;
        }
        </style>
        </head>
        <body>
            <div id="container">
                <div>[!APP-FAILED!]</div>
                <h1><?php echo $heading; ?></h1>
                <?php echo $message; ?>
            </div>
        </body>
        </html>
                
        ENDOFTEXT;
    }
}