<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class intervolga_migrato extends CModule
{
	/**
	 * @return string
	 */
	public static function getModuleId()
	{
		return basename(dirname(__DIR__));
	}

	public function __construct()
	{
		$arModuleVersion = array();
		include(dirname(__FILE__) . "/version.php");
		$this->MODULE_ID = self::getModuleId();
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::getMessage("INTERVOLGA_MIGRATO_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("INTERVOLGA_MIGRATO_MODULE_DESC");

		$this->PARTNER_NAME = Loc::getMessage("INTERVOLGA_MIGRATO_PARTNER_NAME");
		$this->PARTNER_URI = Loc::getMessage("INTERVOLGA_MIGRATO_PARTNER_URI");
	}

	public function doInstall()
	{
		try
		{
			$this->installDb();
			$this->installFiles();
			Main\ModuleManager::registerModule($this->MODULE_ID);
		}
		catch (\Exception $e)
		{
			global $APPLICATION;
			$APPLICATION->ThrowException($e->getMessage());

			return false;
		}

		return true;
	}

	public function installDb()
	{
		global $DB, $DBType;
		$errors = $DB->RunSQLBatch(__DIR__. "/db/" . strtolower($DBType) . "/install.sql");
		if ($errors)
		{
			throw new \Exception(implode("<br>", $errors));
		}

		return true;
	}

	public function installFiles()
	{
		$targetDirectoryPath = Main\Application::getDocumentRoot() . "/bitrix/tools/" . $this->MODULE_ID . "/";
		checkDirPath($targetDirectoryPath);
		$toolsDirectory = new Directory(dirname(__DIR__) . "/tools/");
		foreach ($toolsDirectory->getChildren() as $fileSystemEntry)
		{
			if ($fileSystemEntry instanceof File)
			{
				$content = "<?php\n";
				$content .= "require(\$_SERVER[\"DOCUMENT_ROOT\"].\"/local/modules/" . $this->MODULE_ID . "/tools/" . $fileSystemEntry->getName() . "\");";
				File::putFileContents($targetDirectoryPath . "/" . $fileSystemEntry->getName(), $content);
			}
		}
	}

	public function doUninstall()
	{
		try
		{
			$this->unInstallDb();
			$this->unInstallFiles();
			Main\ModuleManager::unRegisterModule($this->MODULE_ID);
		}
		catch (\Exception $e)
		{
			global $APPLICATION;
			$APPLICATION->ThrowException($e->getMessage());

			return false;
		}

		return true;
	}

	public function unInstallDb()
	{
		global $DB, $DBType;
		$errors = $DB->RunSQLBatch(__DIR__. "/db/" . strtolower($DBType) . "/uninstall.sql");
		if ($errors)
		{
			throw new \Exception(implode("<br>", $errors));
		}

		return true;
	}

	public function unInstallFiles()
	{
		$targetDirectoryPath = Main\Application::getDocumentRoot() . "/bitrix/tools/" . $this->MODULE_ID . "/";
		Directory::deleteDirectory($targetDirectoryPath);
	}
}