<?php
namespace xz1mefx\ufu\models;

use xz1mefx\base\db\ActiveRecord;
use xz1mefx\base\helpers\Url;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * Class UfuActiveRecord
 * @package xz1mefx\ufu\models
 *
 * @property integer               $segmentLevel
 * @property string                $type
 * @property string                $url
 * @property string                $fullPath
 * @property string                $fullPathHash
 * @property string                $webUrl
 *
 * @property string                $typeName
 * @property array                 $categories
 *
 * @property UfuUrl                $ufuUrl
 * @property UfuCategoryRelation[] $ufuCategoryRelations
 */
abstract class UfuActiveRecord extends ActiveRecord
{

    private $_segmentLevel;
    private $_type;
    private $_url;
    private $_fullPath;
    private $_fullPathHash;
    private $_categories;

    /**
     * @return UfuUrl
     */
    abstract public function getUfuUrl();

    /**
     * @return UfuCategoryRelation[]
     */
    abstract public function getUfuCategoryRelations();

    /**
     * @param $type
     *
     * @return ActiveQuery
     */
    public function getUfuUrlByType($type)
    {
        return $this->hasOne(UfuUrl::className(), ['item_id' => 'id'])
            ->andOnCondition(['is_category' => 0, 'type' => $type]);
    }

    /**
     * @param $type
     *
     * @return ActiveQuery
     */
    public function getUfuCategoryRelationsByType($type)
    {
        return $this->hasMany(UfuCategoryRelation::className(), ['item_id' => 'id'])
            ->joinWith('ufuCategory.ufuUrl')
            ->andOnCondition([UfuCategory::TABLE_ALIAS_UFU_URL . '.type' => $type]);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $url = $this->ufuUrl ?: new UfuUrl();
        if ($this instanceof UfuCategory) {
            $url->parent_category_id = $this->parent_id;
            $url->full_path = $this->fullPath;
        } else {
            $url->full_path = $this->url;
        }
        $url->segment_level = $this->segmentLevel;
        $url->is_category = (int)($this instanceof UfuCategory);
        $url->type = $this->type;
        $url->item_id = $this->id;
        $url->url = $this->url;
        $url->save();

        if (!($this instanceof UfuCategory)) {
            foreach ($this->categories as $categoryId) {
                $ufuCategoryRelation = UfuCategoryRelation::find()->where([
                    'category_id' => $categoryId,
                    'item_id' => $this->id,
                ])->one();
                if (!$ufuCategoryRelation) {
                    $ufuCategoryRelation = new UfuCategoryRelation();
                    $ufuCategoryRelation->category_id = (int)$categoryId;
                    $ufuCategoryRelation->item_id = $this->id;
                }
                $ufuCategoryRelation->save();
            }
            foreach ($this->ufuCategoryRelations as $ufuCategoryRelation) {
                if (!in_array($ufuCategoryRelation->category_id, $this->categories)) {
                    $ufuCategoryRelation->delete();
                }
            }
        }
    }

    /**
     * @param $attribute
     * @param $params
     */
    public function validateUfuUrl($attribute, $params)
    {
        // validate fields in UfuUrl model
        $url = $this->ufuUrl ?: new UfuUrl();
        if ($this instanceof UfuCategory) {
            $url->parent_category_id = $this->parent_id;
        }
        $url->segment_level = $this->segmentLevel;
        $url->type = $this->type;
        if (!$this->isNewRecord) {
            $url->item_id = $this->id;
        }
        $url->url = $this->url;
        if (!$url->validate(['segment_level', 'url'])) {
            foreach ($url->errors as $error) {
                $this->addError("url", $error);
            }
        }
    }

    /**
     * @return int
     */
    public function getSegmentLevel()
    {
        if (isset($this->_segmentLevel)) {
            return $this->_segmentLevel;
        }
        if ($this->ufuUrl) {
            return $this->_segmentLevel = $this->ufuUrl->segment_level;
        }
        return $this->_segmentLevel = 1;
    }

    /**
     * @param $value integer
     */
    public function setSegmentLevel($value)
    {
        $this->_segmentLevel = $value;
    }

    /**
     * @return int|null
     */
    public function getType()
    {
        if (isset($this->_type)) {
            return $this->_type;
        }
        if ($this->ufuUrl) {
            return $this->_type = $this->ufuUrl->type;
        }
        return $this->_type = NULL;
    }

    /**
     * @param $value integer
     */
    public function setType($value)
    {
        $this->_type = $value;
    }

    /**
     * @return string|null
     */
    public function getFullPath()
    {
        if (isset($this->_fullPath)) {
            return $this->_fullPath;
        }
        if ($this->ufuUrl) {
            return $this->_fullPath = $this->ufuUrl->full_path;
        }
        return $this->_fullPath = NULL;
    }

    /**
     * @param $value string
     */
    public function setFullPath($value)
    {
        $this->_fullPath = $value;
    }

    /**
     * @return string|null
     */
    public function getFullPathHash()
    {
        if (isset($this->_fullPathHash)) {
            return $this->_fullPathHash;
        }
        if ($this->ufuUrl) {
            return $this->_fullPathHash = $this->ufuUrl->full_path_hash;
        }
        return $this->_fullPathHash = NULL;
    }

    /**
     * @param $value string
     */
    public function setFullPathHash($value)
    {
        $this->_fullPathHash = $value;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        return Yii::$app->ufu->getTypeNameById($this->type);
    }

    /**
     * @return null|string
     */
    public function getUrl()
    {
        if (isset($this->_url)) {
            return $this->_url;
        }
        if ($this->ufuUrl) {
            return $this->_url = $this->ufuUrl->url;
        }
        return $this->_url = NULL;
    }

    /**
     * @param $value string
     */
    public function setUrl($value)
    {
        $this->_url = $value;
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        if (isset($this->_categories)) {
            return $this->_categories;
        }
        if ($this->ufuCategoryRelations) {
            return $this->_categories = ArrayHelper::getColumn($this->ufuCategoryRelations, 'category_id');
        }
        return $this->_categories = [];
    }

    /**
     * @param $value int|array
     */
    public function setCategories($value)
    {
        $this->_categories = is_array($value) ? $value : [$value];
    }

    /**
     * @return string
     */
    public function getWebUrl()
    {
        return Url::to("@weblang/{$this->ufuUrl->full_path}" . Yii::$app->urlManager->suffix);
    }

}
