<?php
namespace xz1mefx\ufu\actions\category;

use xz1mefx\ufu\actions\BaseAction;
use xz1mefx\ufu\models\UfuCategory;
use Yii;
use yii\bootstrap\ActiveForm;
use yii\web\Response;

/**
 * Class CreateAction
 *
 * @property string $theme it can be IndexAction::THEME_BOOTSTRAP or IndexAction::THEME_ADMINLTE
 * @property string $view  the view name (if need to override)
 *
 * @package xz1mefx\ufu\actions\category
 */
class CreateAction extends BaseAction
{

    /**
     * @return string|\yii\web\Response
     */
    public function run()
    {
        $model = new UfuCategory();

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('ufu-tools', 'Category created successfully!'));
            return $this->controller->redirect(['index']);
        } else {
            return $this->controller->render(
                $this->view ?: "@vendor/xz1mefx/yii2-ufu/views/category/{$this->theme}/create",
                [
                    'model' => $model,
                ]
            );
        }
    }

}