<?php

namespace common\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "order_items".
 *
 * @property string $id
 * @property string $order_id
 * @property string $item_id
 * @property string $price
 * @property integer $quantity
 * @property string $note
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @property OrderItemAddon[] $orderItemAddons
 * @property Addons[] $addons
 * @property OrderItemChoices[] $orderItemChoices
 * @property ItemChoices[] $itemChoices
 * @property MenuItems $item
 */
class OrderItems extends \yii\db\ActiveRecord
{
    const SCENARIO_GET_BY_RESTAURANTS_MANGER = 'get_by_restaurants_manger';
    const SCENARIO_CLIENT_ORDER_DETAILS = 'client_order_details';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_items';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'item_id', 'price', 'quantity'], 'required'],
            [['order_id', 'item_id', 'quantity'], 'integer'],
            [['price'], 'number'],
            [['note'], 'string'],
            [['quantity'], 'compare', 'compareValue' => 0, 'operator' => '>'],
            [['created_at', 'updated_at', 'deleted_at'], 'safe'],
            [['item_id'], 'exist', 'skipOnError' => true, 'targetClass' => MenuItems::className(), 'targetAttribute' => ['item_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'item_id' => 'Item ID',
            'price' => 'Price',
            'quantity' => 'Quantity',
            'note' => 'Note',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItemAddons()
    {
        return $this->hasMany(OrderItemAddon::className(), ['order_item_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAddons()
    {
        return $this->hasMany(Addons::className(), ['id' => 'addon_id'])->viaTable('order_item_addon', ['order_item_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItemChoices()
    {
        return $this->hasMany(OrderItemChoices::className(), ['order_item_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItemChoices()
    {
        return $this->hasMany(ItemChoices::className(), ['id' => 'item_choice_id'])->viaTable('order_item_choices', ['order_item_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItem()
    {
        return $this->hasOne(MenuItems::className(), ['id' => 'item_id']);
    }

    /**
     * @inheritdoc
     * @return OrderItemsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new OrderItemsQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if (!$this->isNewRecord)
            $this->updated_at = date('Y-m-d H:i:s');
        else
            $this->created_at = date('Y-m-d H:i:s');

        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    public function scenarios()
    {
        return ArrayHelper::merge(
            parent::scenarios(),
            [
                self::SCENARIO_GET_BY_RESTAURANTS_MANGER => [
                    'id' => function () {
                        return (int)$this->id;
                    },
                    'price' => function () {
                        return (float)$this->price;
                    },
                    'quantity' => function () {
                        return (int)$this->quantity;
                    },
                    'note' => function () {
                        return (string)$this->note;
                    },
                    'menu_item' => function () {
                        $item = array();
                        $item['id'] = (int)$this->item->id;
                        $item['name'] = (string)$this->item->name;
                        $item['description'] = (string)$this->item->description;
                        $item['price'] = (float)$this->item->price;
                        $item['status'] = (bool)$this->item->status;
                        $item['discount'] = (int)$this->item->discount;
                        $item['image'] = (string)$this->item->image;
                        $item['is_taxable'] = (bool)$this->item->is_taxable;
                        $item['is_verified'] = (bool)$this->item->is_verified;
                        $restaurant = Restaurants::checkRestaurantAccess();
                        $item['categories'] = MenuItems::getMenuItemCategories($restaurant->id, $this->item->id);
                        return $item;
                    },
                    'addons' => function () {
                        return $this->orderItemAddons;
                    },
                    'item_choices' => function () {
                        return $this->orderItemChoices;
                    }
                ],
                self::SCENARIO_CLIENT_ORDER_DETAILS => [
                    'id' => function () {
                        return (int)$this->id;
                    },
                    'ordered_price' => function () {
                        return (float)$this->price;
                    },
                    'ordered_quantity' => function () {
                        return (int)$this->quantity;
                    },
                    'ordered_note' => function () {
                        return (string)$this->note;
                    },
                    'ordered_menu_item' => function () {
                      return $this->item;
                    },
                    'ordered_addons' => function () {
                        return $this->orderItemAddons;
                    },
                    'ordered_item_choices' => function () {
                        return $this->orderItemChoices;
                    }
                ],
            ]);
    }

    public function fields()
    {
        $request = Yii::$app->request;
        $get_data = $request->get();

        $request_action = explode('/', Yii::$app->getRequest()->getUrl());
        if ((in_array('clients', $request_action) && in_array('orders', $request_action)) && $request->isGet) {
            if (!empty($get_data) && isset($get_data['id']))
                return $this->scenarios()[self::SCENARIO_CLIENT_ORDER_DETAILS];
        } else if (in_array('orders', $request_action) && Yii::$app->request->isGet && isset(Yii::$app->request->get()['id']))
            return $this->scenarios()[self::SCENARIO_GET_BY_RESTAURANTS_MANGER];
        return parent::fields();
    }
}
