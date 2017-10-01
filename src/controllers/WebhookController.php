<?php
namespace enupal\backup\controllers;

use Craft;
use craft\web\Controller as BaseController;

use enupal\backup\Backup;

class WebhookController extends BaseController
{
	protected $allowAnonymous = array('actionFinished');

	/**
	 * Webhook to listen when a backup process finish up
	 * @param $backupId
	 *
	*/
	public function actionFinished()
	{
		$backupId = Craft::$app->request->getParam('backupId');
		$backup   = Backup::$app->backups->getBackupByBackupId($backupId);
		$settings = Backup::$app->settings->getSettings();

		if ($backup)
		{
			// we could check just this backup but let's check all pending backups
			$pendingBackups = Backup::$app->backups->getPendingBackups();

			foreach ($pendingBackups as $key => $backup)
			{
				$result = Backup::$app->backups->updateBackupOnComplete($backup);
				// let's send a notification
				if ($result && $settings->enableNotification)
				{
					Backup::$app->backups->sendNotification($backup);
				}

				Backup::info("EnupalBackup: ".$backup->backupId." Status:".$backup->backupStatusId." (webhook)");
			}

			Backup::$app->backups->checkBackupsAmount();
			Backup::$app->backups->deleteConfigFile();
		}
		else
		{
			Backup::error("Unable to finish the webhook backup with id: ".$backupId);
		}

		return $this->asJson(['success'=>true]);
	}
}
