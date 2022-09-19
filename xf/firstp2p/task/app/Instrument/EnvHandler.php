<?php
namespace NCFGroup\Task\Instrument;

class EnvHandler
{
    private static function getApp()
    {/*{{{*/
        $appArr = array(
        'p2p',
        'fund',
    );

        echo <<<AAA
1 - p2p
2 - fund
请选择应用序号:
AAA;

        return $appArr[trim(fgets(STDIN)) - 1];
    }/*}}}*/

    private static function getEnv()
    {/*{{{*/
        $envArr = array(
        'dev',
        'product',
        'test',
    );

        echo <<<AAA
1 - dev
2 - product
3 - test
请选择应用序号:
AAA;

        return $envArr[trim(fgets(STDIN)) - 1];
    }/*}}}*/

    public static function requireInit()
    {/*{{{*/
        $app = self::getApp();
        $env = self::getEnv();

        if ($app == 'p2p') {
            if ($env == 'product') {
                require '/apps/product/nginx/htdocs/firstp2p/scripts/init.php';
            } elseif ($env == 'dev') {
                require '/home/dev/git/firstp2p/scripts/init.php';
            } elseif ($env == 'test') {
                require '/apps/product/nginx/htdocs/firstp2p/firstp2p/scripts/init.php';
            }
        } elseif ($app == 'fund') {
            if ($env == 'product') {
                require '/apps/product/nginx/htdocs/fundgate/backend/app/tasks/init4worker.php';
            } elseif ($env == 'dev') {
                require '/home/dev/git/fundgate/backend/app/tasks/init4worker.php';
            } elseif ($env == 'test') {
                require '/apps/product/nginx/htdocs/fundgate/backend/app/tasks/init4worker.php';
            }
        }

    }/*}}}*/
}
