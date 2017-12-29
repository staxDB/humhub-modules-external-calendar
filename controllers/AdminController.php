<?php

namespace humhub\modules\external_calendar\controllers;


use Yii;
use yii\helpers\Url;
use humhub\modules\admin\components\Controller;
use humhub\modules\external_calendar\models\ConfigForm;

/**
 * AdminController implements the config actions for all external calendars
 *
 * @author davidborn
 */
class AdminController extends Controller
{
    /**
     * Lists config model for calendar extension.
     * @return mixed
     */
    public function actionIndex()
    {
        $form = new ConfigForm();

        if ($form->load(Yii::$app->request->post()) && $form->validate() && $form->save()) {
            return $this->redirect(Url::to(['/admin/module/list']));
        }

        return $this->render('index', ['model' => $form]);

    }

}
