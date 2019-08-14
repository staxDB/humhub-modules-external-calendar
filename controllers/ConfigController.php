<?php

namespace humhub\modules\external_calendar\controllers;


use Yii;
use humhub\modules\admin\components\Controller;
use humhub\modules\external_calendar\models\forms\ConfigForm;

/**
 * ConfigController implements the config actions for all external calendars
 *
 * @author David Born ([staxDB](https://github.com/staxDB))
 */
class ConfigController extends Controller
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @return mixed
     */
    public function actionIndex()
    {
        $form = new ConfigForm();

        if ($form->load(Yii::$app->request->post()) && $form->save()) {
            $this->view->saved();
        }

        return $this->render('index', ['model' => $form]);

    }

}
