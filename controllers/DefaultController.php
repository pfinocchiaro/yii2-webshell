<?php
namespace capoloja\webshell\controllers;

use Yii;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;

/**
 * DefaultController
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 *
 * @property \capoloja\webshell\Module $module
 */
class DefaultController extends Controller
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        Yii::$app->request->enableCsrfValidation = false;
        parent::init();
    }

    /**
     * Displays initial HTML markup
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = 'shell';
        return $this->render('index', [
            'quitUrl' => $this->module->quitUrl ? Url::toRoute($this->module->quitUrl) : null,
            'greetings' => $this->module->greetings
        ]);
    }

    /**
     * RPC handler
     * @return array
     */
    public function actionRpc()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $options = Json::decode(Yii::$app->request->getRawBody());
        switch ($options['method']) {
            case 'yii':
                list ($status, $output) = $this->runConsole(implode(' ', $options['params']));
                return ['result' => $output];
				break;
            case 'svn':
                list ($status, $output) = $this->runSvn(implode(' ', $options['params']));
                return ['result' => $output];
				break;
            case 'pwd':
            case 'cd':
                list ($status, $output) = $this->runOSCmd($options['method'].' '.implode(' ', $options['params']));
                return ['result' => $output];
				break;
        }
    }

    /**
     * Runs console command
     *
     * @param string $command
     *
     * @return array [status, output]
     */
    private function runConsole($command)
    {
        $cmd = Yii::getAlias($this->module->yiiScript) . ' ' . $command . ' 2>&1';

        $handler = popen($cmd, 'r');
        $output = '';
        while (!feof($handler)) {
            $output .= fgets($handler);
        }

        $output = trim($output);
        $status = pclose($handler);

        return [$status, $output];
    }
	
    /**
     * Runs console command
     *
     * @param string $command
     *
     * @return array [status, output]
     */
    private function runSvn($command)
    {
		chdir(Yii::getAlias('@base'));
        $cmd = 'svn ' . $command . ' 2>&1';

        $handler = popen($cmd, 'r');
        $output = '';
        while (!feof($handler)) {
            $output .= fgets($handler);
        }

        $output = trim($output);
        $status = pclose($handler);

        return [$status, $output];
    }
	
    private function runOSCmd($command)
    {
		//chdir(dirname(__DIR__));
        $cmd = $command . ' 2>&1';

        $handler = popen($cmd, 'r');
        $output = '';
        while (!feof($handler)) {
            $output .= fgets($handler);
        }

        $output = trim($output);
        $status = pclose($handler);

        return [$status, $output];
    }
}