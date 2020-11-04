<?php

namespace humhub\modules\external_calendar\controllers;

use humhub\modules\external_calendar\models\ICalExpand;
use Yii;
use yii\web\HttpException;
use humhub\widgets\ModalClose;
use humhub\modules\content\components\ContentContainerController;
use humhub\modules\external_calendar\permissions\ManageEntry;
use humhub\modules\external_calendar\models\ExternalCalendarEntry;
use yii\web\NotFoundHttpException;

/**
 * EntryController implements the CRUD actions for all external calendar entries
 *
 * @author David Born ([staxDB](https://github.com/staxDB))
 */
class EntryController extends ContentContainerController
{

    /**
     * @inheritdoc
     */
    public $hideSidebar = true;

    public function getAccessRules()
    {
        return[
            ['permission' => ManageEntry::class, 'actions' => ['update']]
        ];
    }

    /**
     * Displays a single ExternalCalendarEntry model.
     * @param integer $id
     * @param null $cal
     * @return mixed
     * @throws HttpException
     * @throws \yii\base\Exception
     * @throws \Throwable
     */
    public function actionView($id, $cal = null)
    {
        return $this->renderEntry($this->getCalendarEntry($id), $cal);
    }

    private function renderEntry($model, $cal = null)
    {
        // We need the $cal information, since the update redirect in case of fullcalendar view is other than stream view
        if ($cal) {
            return $this->renderModal($model, $cal);
        }

        return $this->render('view', ['model' => $model,]);
    }

    private function renderModal($model, $cal)
    {
        return $this->renderAjax('modal', [
            'model' => $model,
            'editUrl' => $this->contentContainer->createUrl('/external_calendar/entry/update', ['id' => $model->id, 'cal' => $cal]),
            'canManageEntries' => $model->content->canEdit(),
            'contentContainer' => $this->contentContainer,
        ]);
    }

    /**
     * @param $parent_id
     * @param $recurrence_id
     * @param null $cal
     * @return string
     * @throws \Throwable
     * @throws \yii\base\Exception
     */
    public function actionViewRecurrence($parent_id, $recurrence_id, $cal = null)
    {
        $recurrenceRoot = $this->getCalendarEntry($parent_id);
        $recurrence = $recurrenceRoot->getRecurrenceInstance($recurrence_id);

        if($recurrence) {
            return $this->renderEntry($recurrence, $cal);
        }

        $recurrence = ICalExpand::expandSingle($recurrenceRoot, $recurrence_id);

        if(!$recurrence) {
            throw new NotFoundHttpException();
        }

        return $this->renderEntry($recurrence, $cal);
    }

    /**
     * Updates an existing ExternalCalendarEntry model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @param null $cal
     * @return mixed
     * @throws HttpException
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionUpdate($id, $cal = null)
    {
        $model = $this->getCalendarEntry($id);

        if (!$model->content->canEdit()) {
            throw new HttpException(403);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if (empty($cal)) {
                return ModalClose::widget(['saved' => true]);
            }

            return $this->renderModal($model, 1);
        }

        return $this->renderAjax('update', [
            'model' => $model,
            'contentContainer' => $this->contentContainer,
            'editUrl' => $this->contentContainer->createUrl('/external_calendar/entry/update', ['id' => $model->id, 'cal' => $cal]),
        ]);
    }

    /**
     * Returns a readable calendar entry by given id
     *
     * @param int $id
     * @return ExternalCalendarEntry
     * @throws \yii\base\Exception
     * @throws \Throwable
     */
    protected function getCalendarEntry($id)
    {
        $entry = ExternalCalendarEntry::find()->contentContainer($this->contentContainer)->readable()->where(['external_calendar_entry.id' => $id])->one();

        if(!$entry) {
            throw new NotFoundHttpException();
        }

        return $entry;
    }

    /**
     * @return \yii\console\Response|\yii\web\Response
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\web\RangeNotSatisfiableHttpException
     */
    public function actionGenerateics($id)
    {
        $calendarEntry = $this->getCalendarEntry($id);
        $ics = $calendarEntry->generateIcs();
        return Yii::$app->response->sendContentAsFile($ics, uniqid('calendar', true) . '.ics', ['mimeType' => 'text/calendar']);
    }
}
