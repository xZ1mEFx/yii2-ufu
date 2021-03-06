<?php
namespace xz1mefx\ufu\models;

use xz1mefx\multilang\models\Language;
use xz1mefx\multilang\models\Message;
use xz1mefx\multilang\models\SourceMessage;
use xz1mefx\ufu\models\traits\CategoryTreeTrait;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * This is the model class for table "{{%ufu_category}}".
 *
 * @property integer                $id
 * @property integer                $is_section
 * @property integer                $parent_id
 * @property string                 $parents_list
 * @property string                 $children_list
 * @property integer                $created_at
 * @property integer                $updated_at
 *
 * @property UfuCategoryTranslate   $ufuCategoryTranslate
 * @property string                 $name
 *
 * @property UfuCategoryTranslate   $parentUfuCategoryTranslate
 * @property string                 $parentName
 *
 * @property integer                $segmentLevel
 * @property string                 $type
 * @property string                 $url
 *
 * @property string                 $typeName
 *
 * @property integer                $relationsCount
 * @property integer                $parentsCount
 * @property integer                $childrenCount
 *
 * @property bool                   $canUpdateType
 * @property bool                   $canDelete
 *
 * @property UfuCategoryRelation[]  $ufuCategoryRelations
 * @property UfuCategoryTranslate[] $ufuCategoryTranslates
 * @property UfuUrl                 $ufuUrl
 * @property UfuCategory            $parent
 */
class UfuCategory extends UfuActiveRecord
{

    use CategoryTreeTrait;

    const TABLE_ALIAS_PARENT_UFU_CATEGORY = 'puc';
    const TABLE_ALIAS_UFU_CATEGORY_TRANSLATE = 'uct';
    const TABLE_ALIAS_PARENT_UFU_CATEGORY_TRANSLATE = 'puct';
    const TABLE_ALIAS_UFU_URL = 'uu';

    public $needToUpdateTree;
    public $ctree_error;

    private $_multilangNames;
    private $_canUpdateType;
    private $_canDelete;
    private $_relationsCount;

    /**
     * @inheritdoc
     */
    public function beforeValidate()
    {
        if ($this->needToUpdateTree) {
            $this->segmentLevel = ArrayHelper::getValue($this->parent, 'segmentLevel', 0) + 1;
            // validate translate fields in their models
            if (isset($this->_multilangNames)) {
                foreach ($this->_multilangNames as $langId => $name) {
                    $translateModel = new UfuCategoryTranslate();
                    $translateModel->name = $name;
                    if (!$translateModel->validate(['name'])) {
                        foreach ($translateModel->errors as $error) {
                            $this->addError("multilangNames[$langId]", $error);
                        }
                    }
                }
            }
        }
        return parent::beforeValidate();
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete()) {
            if ($this->canDelete) {
                return TRUE;
            }
            Yii::$app->session->setFlash('error', Yii::t('ufu-tools', 'You can delete the category only without relations, parents and children'));
        }
        return FALSE;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        // update all cached fields in current categories tree
        $this->updateCategoryTree();
        // delete related url
        UfuUrl::deleteAll([
            'is_category' => 1,
            'item_id' => $this->id,
        ]);
        parent::afterDelete();
    }

    /**
     * Update all cached fields in current categories tree
     */
    private function updateCategoryTree()
    {
        // lock tables
        self::lockTables([
            self::tableName(),
            UfuCategoryRelation::tableName(),
            UfuCategoryTranslate::tableName(),
            self::TABLE_ALIAS_PARENT_UFU_CATEGORY_TRANSLATE => UfuCategoryTranslate::tableName(),
            UfuUrl::tableName(),
            self::TABLE_ALIAS_UFU_URL => UfuUrl::tableName(),
            Language::tableName(),
            Message::tableName(),
            SourceMessage::tableName(),
        ]);
        // clear cached data
        self::resetItemsIdTreeCache();
        // get flat tree array
        $itemsTreeList = self::collectItemsIdTree(TRUE);
        // refresh data
        if (isset($itemsTreeList[$this->id])) {
            $categoriesToUpdate = array_unique(
                array_merge(
                    [$this->id],
                    Json::decode($this->oldAttributes['parents_list']),
                    Json::decode($this->oldAttributes['children_list']),
                    $itemsTreeList[$this->id]['parents_id_list'],
                    $itemsTreeList[$this->id]['children_id_list']
                )
            );
            $categoriesToUrlUpdate = array_unique(
                array_merge(
                    [$this->id],
                    Json::decode($this->oldAttributes['children_list']),
                    $itemsTreeList[$this->id]['children_id_list']
                )
            );
            foreach (self::find()->joinWith('ufuUrl')->where([self::tableName() . '.id' => $categoriesToUpdate])->all() as $category) {
                // Update category
                $category->parents_list = Json::encode($itemsTreeList[$category->id]['parents_id_list']);
                $category->children_list = Json::encode($itemsTreeList[$category->id]['children_id_list']);
                $category->segmentLevel = $itemsTreeList[$category->id]['level'];
                if (in_array($category->id, $categoriesToUrlUpdate)) {
                    $category->type = $this->type;
                    $category->fullPath = $itemsTreeList[$category->id]['full_path'];
                }
                $category->save(FALSE);
            }
        }
        // unlock locked tables
        self::unlockTables();
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ufu_category}}';
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->needToUpdateTree) {
            // update category translates
            if (is_array($this->multilangNames)) {
                $indexedTranslates = ArrayHelper::index($this->ufuCategoryTranslates, 'language_id');
                foreach ($this->multilangNames as $langId => $name) {
                    if (isset($indexedTranslates[$langId])) { // update translate
                        if ($indexedTranslates[$langId]->name != $name) {
                            $indexedTranslates[$langId]->name = $name;
                            $indexedTranslates[$langId]->save();
                        }
                    } else { // insert new translate
                        $translateModel = new UfuCategoryTranslate();
                        $translateModel->category_id = $this->id;
                        $translateModel->language_id = $langId;
                        $translateModel->name = $name;
                        $translateModel->save();
                    }
                }
            }
            // update all cached fields in current categories tree
            $this->updateCategoryTree();
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => time(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // parent id
            ['is_section', 'integer'],
            ['is_section', 'default', 'value' => 0],
            ['is_section', function ($attribute, $params) {
                if (
                    $this->{$attribute} == 1
                    &&
                    UfuCategory::find()
                        ->joinWith('ufuUrl')
                        ->where([
                            $attribute => 0,
                            'parent_id' => 0,
                            self::TABLE_ALIAS_UFU_URL . '.type' => $this->type,
                        ])
                        ->exists()
                ) {
                    $this->addError($attribute, Yii::t('ufu-tools', 'In current category type simple categories already exists!'));
                }
            }],
            // parent id
            ['parent_id', 'integer'],
            ['parent_id', 'default', 'value' => 0],
            ['parent_id', function ($attribute, $params) {
                if ($this->{$attribute} == 0) {
                    if (
                        $this->is_section == 0
                        &&
                        UfuCategory::find()
                            ->joinWith('ufuUrl')
                            ->where([
                                'is_section' => 1,
                                self::TABLE_ALIAS_UFU_URL . '.type' => $this->type,
                            ])
                            ->exists()
                    ) {
                        $this->addError('ctree_error', Yii::t('ufu-tools', 'You cannot save parent category because current category type have category sections!'));
                    }
                } else {
                    $parent = UfuCategory::findOne($this->{$attribute});
                    if ($parent) {
                        if ($parent->type != $this->type) {
                            $this->addError('ctree_error', Yii::t('ufu-tools', 'Type of parent category is different!'));
                        }
                    } else {
                        $this->addError('ctree_error', Yii::t('ufu-tools', 'Parent category is not exist'));
                    }
                }
            }],
            // parents list
            ['parents_list', 'string'],
            ['parents_list', 'default', 'value' => '[]'],
            // children list
            ['children_list', 'string'],
            ['children_list', 'default', 'value' => '[]'],
            // created-updated timestamps
            [['created_at', 'updated_at'], 'integer'],
            // virtual segment level field
            ['segmentLevel', 'integer'],
            ['segmentLevel', 'default', 'value' => 1],
            // virtual type field
            ['type', 'required'],
            ['type', 'integer'],
            ['type', 'in', 'range' => Yii::$app->ufu->getTypesIdList()],
            // virtual url field
            ['url', 'required'],
            ['url', 'validateUfuUrl'],
            // virtual multilang names fields
            ['multilangNames', 'safe'],
            // need to update tree flag
            ['needToUpdateTree', 'safe'],
            // category tree errors output
            ['ctree_error', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('ufu-tools', 'ID'),
            'type' => Yii::t('ufu-tools', 'Type'),
            'is_section' => Yii::t('ufu-tools', 'Is Section'),
            'parent_id' => Yii::t('ufu-tools', 'Parent ID'),
            'parents_list' => Yii::t('ufu-tools', 'Parents List'),
            'children_list' => Yii::t('ufu-tools', 'Children List'),
            'created_at' => Yii::t('ufu-tools', 'Created At'),
            'updated_at' => Yii::t('ufu-tools', 'Updated At'),
            'name' => Yii::t('ufu-tools', 'Name'),
            'typeName' => Yii::t('ufu-tools', 'Type'),
            'relationsCount' => Yii::t('ufu-tools', 'Relations count'),
            'parentsCount' => Yii::t('ufu-tools', 'Parents count'),
            'childrenCount' => Yii::t('ufu-tools', 'Children count'),
            'multilangNames' => Yii::t('ufu-tools', 'Name'),
            'parentName' => Yii::t('ufu-tools', 'Parent name'),
        ];
    }

    /**
     * @return array
     */
    public function getMultilangNames()
    {
        if (isset($this->_multilangNames)) {
            return $this->_multilangNames;
        }
        $this->_multilangNames = [];
        foreach (Yii::$app->lang->getLangList() as $lang) {
            $this->_multilangNames[$lang['id']] = ArrayHelper::getValue(
                ArrayHelper::map($this->ufuCategoryTranslates, 'language_id', 'name'),
                $lang['id']
            );
        }
        return $this->_multilangNames;
    }

    /**
     * @param $value array
     */
    public function setMultilangNames($value)
    {
        $this->_multilangNames = $value;
    }

    /**
     * @return int
     */
    public function getRelationsCount()
    {
        if ($this->_relationsCount === NULL) {
            return $this->_relationsCount = ($this->isNewRecord ? 0 : UfuCategoryRelation::find()->where(['category_id' => $this->id])->count('id'));
        }
        return $this->_relationsCount;
    }

    /**
     * @return int
     */
    public function getParentsCount()
    {
        return count(Json::decode($this->parents_list));
    }

    /**
     * @return int
     */
    public function getChildrenCount()
    {
        return count(Json::decode($this->children_list));
    }

    /**
     * @return bool
     */
    public function getCanUpdateType()
    {
        if (isset($this->_canUpdateType)) {
            return $this->_canUpdateType;
        }
        return $this->_canUpdateType = $this->relationsCount == 0
            && (empty($this->parents_list) || $this->parents_list == '[]' || $this->parents_list == '{}')
            && (empty($this->children_list) || $this->children_list == '[]' || $this->children_list == '{}');
    }

    /**
     * @return bool
     */
    public function getCanDelete()
    {
        if (isset($this->_canDelete)) {
            return $this->_canDelete;
        }
        return $this->_canDelete = $this->canUpdateType;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUfuCategoryRelations()
    {
        return $this->hasMany(UfuCategoryRelation::className(), ['category_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUfuCategoryTranslate()
    {
        return $this
            ->hasOne(UfuCategoryTranslate::className(), ['category_id' => 'id'])
            ->from([self::TABLE_ALIAS_UFU_CATEGORY_TRANSLATE => UfuCategoryTranslate::tableName()])
            ->andOnCondition([self::TABLE_ALIAS_UFU_CATEGORY_TRANSLATE . '.language_id' => Yii::$app->lang->id]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return empty($this->ufuCategoryTranslate->name) ? Yii::t('ufu-tools', '<i>(has no translation)</i>') : $this->ufuCategoryTranslate->name;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentUfuCategoryTranslate()
    {
        return $this
            ->hasOne(UfuCategoryTranslate::className(), ['category_id' => 'parent_id'])
            ->from([self::TABLE_ALIAS_PARENT_UFU_CATEGORY_TRANSLATE => UfuCategoryTranslate::tableName()])
            ->andOnCondition([self::TABLE_ALIAS_PARENT_UFU_CATEGORY_TRANSLATE . '.language_id' => Yii::$app->lang->id]);
    }

    /**
     * @return string
     */
    public function getParentName()
    {
        if ($this->parent_id == 0) {
            return '';
        }
        return empty($this->parentUfuCategoryTranslate->name) ? Yii::t('ufu-tools', '<i>(has no translation)</i>') : $this->parentUfuCategoryTranslate->name;
    }

    /**
     * @inheritdoc
     */
    public function getUfuUrl()
    {
        return $this->hasOne(UfuUrl::className(), ['item_id' => 'id'])
            ->andOnCondition(['is_category' => 1])
            ->from([self::TABLE_ALIAS_UFU_URL => UfuUrl::tableName()]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this
            ->hasOne(self::className(), ['id' => 'parent_id'])
            ->from([self::TABLE_ALIAS_PARENT_UFU_CATEGORY => self::tableName()]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUfuCategoryTranslates()
    {
        return $this->hasMany(UfuCategoryTranslate::className(), ['category_id' => 'id']);
    }

}
