<?php

class AdminController extends Controller {
	var $layout = 'admincolumn';
	var $userActions = array(
		'ban' => array(4, 'забанен'),
		'unban' => array(1, 'разбанен'),
		'levelup' => array(3, 'Повышен'),
		'active' => array(1, 'активирован'),
		'dismiss' => array(1, 'понижен'),
		);
	public function filters() {
		return array(
			'accessControl',
			);
	}

	public function accessRules() {
		return array(
			array('allow',
				'users' => array('@'),
				'expression' => 'Yii::app()->user->isAdmin'
				),
			array('deny',
				'users' => array('*'),
				),
			);
	}
	
	public function actionIndex() {
		$this->render('index', array('title' => Yii::t('admin', 'Главная страница'), 'content' => Yii::t('admin', 'Это админка<br>Ваш К.О.')));
	}
	public function actionStat($type = 'disk') {
		Yii::import('ext.moment.index', 1);
		$momentManager = new momentManager;
		$stat = $momentManager->stat($type);
		if(empty($stat)) {
			$this->render('stat', array('title' => Yii::t('admin', 'Статистика недоступна'), 'stat' => array()));
			Yii::app()->end();
		}
		switch ($type) {
			case 'disk':
			$stat = json_decode($stat, 1);
			foreach($stat as $k => $s) {
				foreach ($s['disk info'] as $key => $value) {
					$s['disk info'][$key] = $this->convertSize($value);
				}
				$stat[$k] = $s;
			}
			$all = $stat;
			break;      

			case 'rtmp':
			$all = str_replace(array('<html><body>', '</html></body>'), '', $stat);
			break;        

			case 'load':
			$stat = json_decode($stat, 1);
			$all = array();
			$stat = array_reverse($stat['statistics']);
			$stat = array_slice($stat, 0, 100);
			$stat = array_reverse($stat);
			foreach($stat as $key => $value) {
				$all['time'][] = $value['time'];
				foreach($value as $k => $v) {
					if($k == 'time') { continue; }
					$all[$k]['min'][] = (float)$v['min'];
					$all[$k]['max'][] = (float)$v['max'];
					$all[$k]['avg'][] = (float)$v['avg'];
				}
			}
			break;

			default:
			$all = array();
			break;
		}

		$this->render('stat/index', array('title' => Yii::t('admin', 'Статистика(100 последних измерений)'), 'stat' => $all, 'type' => $type));
	}

	function convertSize($s) {
		if (is_int($s)) {
			$s = sprintf("%u", $s);		
		} if($s >= 1073741824) {
			return sprintf('%1.2f', $s / 1073741824 ). ' GB';
		} elseif($s >= 1048576) {
			return sprintf('%1.2f', $s / 1048576 ) . ' MB';
		} elseif($s >= 1024) {
			return sprintf('%1.2f', $s / 1024 ) . ' KB';
		} else {
			return $s . ' B';
		}
		$this->render('stat', array('title' => 'Статистика(100 последних измерений)', 'stat' => $all));
	}

	public function actionCams() {
		$id = Yii::app()->user->getId();
		if(isset($_POST['CamsForm']) && !empty($_POST['CamsForm']) && array_sum($_POST['CamsForm']) != 0) {
			if(isset($_POST['public'])) {
				foreach ($_POST['CamsForm'] as $key => $cam) {
					if($cam) {
						$key = explode('_', $key);
						$cam = Cams::model()->findByPK((int)$key[1]);
						if($cam) {
							$cam->public = $cam->public ? 0 : 1;
							if($cam->save()) {
								Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Настройки камер изменены')));
							}
						}
					}
				}
			}
		}
		$myCams = Cams::model()->findAllByAttributes(array('user_id' => $id));
		$public = Cams::model()->findAllByAttributes(array('public' => 1));
		$criteria = new CDbCriteria();
		$criteria->addNotInCondition('id', CHtml::listData($myCams, 'id', 'id'));
		$criteria->addNotInCondition('id', CHtml::listData($public, 'id', 'id'));
		$count = Cams::model()->count($criteria);
		$pages = new CPagination($count);
		$pages->pageSize = 10;
		$pages->applyLimit($criteria);
		$all = Cams::model()->findAll($criteria);
		$this->render(
			'cams/index',
			array(
				'form' => new CamsForm,
				'myCams' => $myCams,
				'publicCams' => $public,
				'allCams' => $all,
				'pages' => $pages
				)
			);
	}

	public function actionUsers() {
		if(isset($_POST['UsersForm']) && !empty($_POST['UsersForm']) && array_sum($_POST['UsersForm']) != 0) {
			foreach ($_POST['UsersForm'] as $key => $user) {
				if($user) {
					$key = explode('_', $key);
					$user = Users::model()->findByPK((int)$key[1]);
					if($user) {
						$this->userAction($_POST, $user);
					}
				}
			}
		}
		$admins = Users::model()->findAllByAttributes(array('status' => 3));
		$operators = Users::model()->findAllByAttributes(array('status' => 2));
		$viewers = Users::model()->findAllByAttributes(array('status' => 1));
		$banned = Users::model()->findAllByAttributes(array('status' => 4));
		$criteria = new CDbCriteria();
		$criteria->addNotInCondition('id', CHtml::listData($admins, 'id', 'id'));
		$criteria->addNotInCondition('id', CHtml::listData($banned, 'id', 'id'));
		$criteria->addNotInCondition('id', CHtml::listData($operators, 'id', 'id'));
		$criteria->addNotInCondition('id', CHtml::listData($viewers, 'id', 'id'));
		$count = Users::model()->count($criteria);
		$pages = new CPagination($count);
		$pages->pageSize = 10;
		$pages->applyLimit($criteria);
		$all = Users::model()->findAll($criteria);
		$this->render(
			'users/index',
			array(
				'form' => new UsersForm,
				'admins' => $admins,
				'operators' => $operators,
				'viewers' => $viewers,
				'banned' => $banned,
				'all' => $all,
				'pages' => $pages
				)
			);
	}

	private function userAction($actions, $user) {
		foreach ($this->userActions as $key => $value) {
			if(isset($actions[$key])) {
				if ($key === 'levelup') {
					$user->status = $user->status + 1;
				} elseif ($key === 'dismiss') {
					$user->status = $user->status - 1;
				} else {
					$user->status = $value[0];
				}
				if($user->save()) {
					Yii::app()->user->setFlash('notify', array('type' => 'success', 'message' => Yii::t('admin', 'Пользователь(и) {action}', array('{action}' => $value[1]))));
					return;
				}
			}
		}	
	}

	public function actionLogs($type) {
		if($type == 'system') {
			$logs = Notifications::model()->findAllByAttributes(array('creator_id' => 0));
		} else {
			$logs = Notifications::model()->findAll(array('condition' => 'creator_id > 0'));
		}
		$this->render('logs/index', array('type' => $type, 'logs' => $logs));

	}
}