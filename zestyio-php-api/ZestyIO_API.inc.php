<?php

/*
	ZestyIO API in PHP
	@author	Brian Blood - brian@object13.com
	@date - July 8, 2024
	@license - MIT

	This code is modeled after the Node.js zestyio-api-wrapper.js
			https://github.com/zesty-io/zestyio-node-api-wrapper

	Also updates at the new NPMJS repo applied.
			https://github.com/zesty-io/node-sdk/

*/




class ZestyIO
{
	protected $apiVersion = 'v1';
	

	protected static $InstanceCache = array();
	public static function ZInstance($instanceZUID)
	{
		if (empty($instanceZUID))				return false;
		if (!key_exists($instanceZUID, self::$InstanceCache))
		{
			self::$InstanceCache[$instanceZUID] = new ZestyIO_Instance($instanceZUID);
		}
		return self::$InstanceCache[$instanceZUID];
	}


}		//	ZestyIO






class ZestyIO_Instance
{
	public $zuid = false;

	protected $authURL = 'https://svc.zesty.io/auth';
	protected $instancesAPIURL = 'https://INSTANCE_ZUID.api.zesty.io/v1';
	protected $accountsAPIURL = 'https://accounts.api.zesty.io/v1';
	protected $mediaAPIURL = 'https://INSTANCE_ZUID.api.zesty.io/v1';
	protected $sitesServiceURL = 'https://svc.zesty.io/sites-service/INSTANCE_ZUID';

	protected $authToken = false;
	protected $logErrors = false;
	protected $logResponses = false;


	const kModelZUIDToken = 'MODEL_ZUID';
	const kItemZUIDToken = 'ITEM_ZUID';
	const kFieldZUIDToken = 'FIELD_ZUID';


	public function __construct($instanceZUID, $Options=array())
	{
		$this->zuid = $instanceZUID;
		if (key_exists('auth_url', $Options))							$this->authURL = $Options['auth_url'];
		if (key_exists('instancesAPIURL', $Options))			$this->instancesAPIURL = $Options['instancesAPIURL'];
		if (key_exists('accountsAPIURL', $Options))				$this->accountsAPIURL = $Options['accountsAPIURL'];
		if (key_exists('mediaAPIURL', $Options))					$this->mediaAPIURL = $Options['mediaAPIURL'];
		if (key_exists('sitesServiceURL', $Options))			$this->sitesServiceURL = $Options['sitesServiceURL'];

		if (key_exists('logErrors', $Options))							$this->logErrors = boolval($Options['logErrors']);
		if (key_exists('logResponses', $Options))							$this->logResponses = boolval($Options['logResponses']);

		
	## Preset any of the URLs that have the instance ZUID in them with our ZUID.
		$this->instancesAPIURL = str_replace('INSTANCE_ZUID', $instanceZUID, $this->instancesAPIURL);
		$this->sitesServiceURL = str_replace('INSTANCE_ZUID', $instanceZUID, $this->sitesServiceURL);
		$this->mediaAPIURL = str_replace('INSTANCE_ZUID', $instanceZUID, $this->mediaAPIURL);
		foreach($this->accountsAPIEndpoints as $k => $uri)
		{
			$this->accountsAPIEndpoints[$k] = str_replace('INSTANCE_ZUID', $instanceZUID, $uri);
		}
	## DONE. No further substitutions will be needed for INSTANCE_ZUID

	}		//	__construct





## ------------------------------------------------------
#mark Models

	public function makeModelAPIURL($modelZUID, $uri_key, $URLVars=array())
	{
  	$uri = $this->makeAPIURL($uri_key);
  	$URLVars[self::kModelZUIDToken] = $modelZUID;
		return str_replace(array_keys($URLVars), $URLVars, $uri);
	}

	public function getModels()
	{
  	$uri = $this->makeAPIURL('modelsGETAll');
  	return $this->RequestGET($uri);
  }

	public function getModel($modelZUID)
	{
  	$uri = $this->makeModelAPIURL($modelZUID, 'modelsGET');
  	return $this->RequestGET($uri);
  }

	public function createModel($modelZUID, $ModelData)
	{
  	$uri = $this->makeAPIURL('modelsPOST');
  	return $this->RequestPOST($uri, $ModelData);
  }



## --------------------
## Model Fields

	public function getFields($modelZUID)
	{
  	$uri = $this->makeModelAPIURL($modelZUID, 'fieldsGETAll');
  	return $this->RequestGET($uri);
  }

	public function getField($modelZUID, $fieldZUID)
	{
		$URLVars = array(self::kFieldZUIDToken => $fieldZUID);
  	$uri = $this->makeModelAPIURL($modelZUID, 'fieldGET', $URLVars);
  	return $this->RequestGET($uri);
  }

	public function createField($modelZUID, $FieldData)
	{
  	$uri = $this->makeModelAPIURL($modelZUID, 'fieldPOST');
  	return $this->RequestPOST($uri, $FieldData);
  }


## --------------------
## Items
/*
      async getItems(
        modelZUID,
        opt = {
          lang: "en-US",
          limit: 5000,
          page: 1,
          _active: 0
        }
      ) {
        if (!modelZUID) {
          throw new Error(
            "SDK:Instance:getItems() missing required `modelZUID` argument"
          );
        }

        let run = true;
        let results = [];
        let currentPage = opt.page;
        let res;

        // Because the API is paginated we need to page
        // through this models items building up the complete
        // set of items to return
        while (run) {
          res = await this.getRequest(
            this.interpolate(this.API.fetchItems, {
              MODEL_ZUID: modelZUID,
              ACTIVE: opt._active,
              PAGE: currentPage,
              LIMIT: opt.limit,
              LANG: opt.lang,
            })
          );

          if (res.statusCode !== 200) {
            throw res;
          }

          if (!res.data.length) {
            run = false;
          } else {
            currentPage++;
            results.push(...res.data);
          }
        }

        res.data = results;

        return res;
      }
*/


  public function findItem($searchTerm)
  {
		$URLVars = array('SEARCH_TERM' => $searchTerm, );
  	$uri = $this->makeInstanceAPIURL('itemsSEARCH', $URLVars);
  	return $this->RequestGET($uri);
  }



	public function getItem($modelZUID, $itemZUID)
	{
		$URLVars = array(self::kItemZUIDToken => $itemZUID);
  	$uri = $this->makeModelAPIURL($modelZUID, 'itemsGET', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function saveItem($modelZUID, $itemZUID, $ItemData)
	{
		$URLVars = array(self::kItemZUIDToken => $itemZUID);
  	$uri = $this->makeModelAPIURL($modelZUID, 'itemsPUT', $URLVars);
  	return $this->RequestPUT($uri, $ItemData);
  }


	public function createItem($modelZUID, $ItemData)
	{
  	$uri = $this->makeModelAPIURL($modelZUID, 'itemsPOST');
  	return $this->RequestPOST($uri, $ItemData);
  }


	public function deleteItem($modelZUID, $itemZUID)
	{
		$URLVars = array('ITEM_ZUID' => $itemZUID, );
  	$uri = $this->makeModelAPIURL($modelZUID, 'itemsDELETE', $URLVars);
  	$ReqOptions = array('usesXAuthHeader' => true, 'responseFormatter' => array($this, 'sitesServiceResponseFormatter'), );
  	return $this->RequestDELETE($uri, $ReqOptions);
  }



/*
      async upsertItem(modelZUID, path, payload) {
        if (!modelZUID) {
          throw new Error(
            "SDK:Instance:upsertItem() missing required `modelZUID` argument"
          );
        }
        if (!path) {
          throw new Error(
            "SDK:Instance:upsertItem() missing required `path` argument"
          );
        }
        if (!payload) {
          throw new Error(
            "SDK:Instance:upsertItem() missing required `payload` argument"
          );
        }

        const res = await this.findItem(path);

        if (Array.isArray(res.data) && res.data.length) {
          const item = res.data.find((item) => item.web.pathPart === path);
          if (item) {
            // Ensure required masterZUID is set for updates
            payload.meta.masterZUID = item.meta.ZUID;

            return await this.updateItem(modelZUID, item.meta.ZUID, payload);
          } else {
            return await this.createItem(modelZUID, payload);
          }
        } else {
          return await this.createItem(modelZUID, payload);
        }
      }
    },
*/
# TODO: https://github.com/zesty-io/node-sdk/blob/master/src/services/instance/items/index.js
#      async publishItems(items) {
#      async unpublishItem(
#    publishItem: "/content/models/MODEL_ZUID/items/ITEM_ZUID/publishings",
#    unpublishItem: "/content/models/MODEL_ZUID/items/ITEM_ZUID/publishings",

	public function publishItemImmediately($modelZUID, $itemZUID, $versionNumber)
	{
    // modelZUID is not required yet, but will be when we move from
    // sites-service to instances-api for this endpoint.  At this 
    // point versionNumber will no longer be required.

		$URLVars = array(self::kModelZUIDToken => $modelZUID, self::kItemZUIDToken => $itemZUID, );
  	$uri = $this->makeSitesServiceAPIURL('schedulePublishPOST', $URLVars);
		$PData = array('version_num' => $versionNumber, );
  	$ReqOptions = array('usesXAuthHeader' => true, 'responseFormatter' => array($this, 'sitesServiceResponseFormatter'), );
  	return $this->RequestPOST($uri, $PData, $ReqOptions);
  }


	public function unpublishItemImmediately($modelZUID, $itemZUID, $publishingZUID)
	{
    // modelZUID is not required yet, but will be when we move from
    // sites-service to instances-api for this endpoint.  At this point
    // publishingZUID will no longer be required.

		$URLVars = array(self::kItemZUIDToken => $itemZUID, 'PUBLISHING_ZUID' => $publishingZUID, );
  	$uri = $this->makeSitesServiceAPIURL('scheduleUnpublishPATCH', $URLVars);
		$PData = array('take_offline_at' => gmdate('Y-m-d H:i:s'), );		//	YYYY-MM-DD HH:mm:ss
  	$ReqOptions = array('usesXAuthHeader' => true, 'responseFormatter' => array($this, 'sitesServiceResponseFormatter'), );
  	return $this->RequestPATCH($uri, $PData, $ReqOptions);
  }

	public function getItemPublishings($modelZUID, $itemZUID)
	{
		$URLVars = array(self::kItemZUIDToken => $itemZUID);
  	$uri = $this->makeModelAPIURL($modelZUID, 'itemsGETPublishings', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function getItemPublishing($modelZUID, $itemZUID, $publishingZUID)
	{
		$URLVars = array(self::kItemZUIDToken => $itemZUID, 'PUBLISHING_ZUID' => $publishingZUID, );
  	$uri = $this->makeModelAPIURL($modelZUID, 'itemsGETPublishing', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function getItems($modelZUID)
	{
  	$uri = $this->makeModelAPIURL($modelZUID, 'itemsGETAll');
  	return $this->RequestGET($uri);
  }


	public function getItemVersions($modelZUID, $itemZUID)
	{
		$URLVars = array(self::kItemZUIDToken => $itemZUID);
  	$uri = $this->makeModelAPIURL($modelZUID, 'itemsGETVersions', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function getItemVersion($modelZUID, $itemZUID, $versionNumber)
	{
		$URLVars = array(self::kItemZUIDToken => $itemZUID, 'VERSION_NUMBER' => $versionNumber, );
  	$uri = $this->makeModelAPIURL($modelZUID, 'itemsGETVersion', $URLVars);
  	return $this->RequestGET($uri);
  }



## ------------------------------------------------------
#mark Views

##	validateView()
##			https://github.com/zesty-io/node-sdk/blob/master/src/services/instance/views/index.js

	public function getViews()
	{
  	$uri = $this->makeInstanceAPIURL('viewsGETAll');
  	return $this->RequestGET($uri);
  }


	public function getView($viewZUID)
	{
		$URLVars = array('VIEW_ZUID' => $viewZUID, );
  	$uri = $this->makeInstanceAPIURL('viewsGET', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function getViewVersions($viewZUID)
	{
		$URLVars = array('VIEW_ZUID' => $viewZUID, );
  	$uri = $this->makeInstanceAPIURL('viewsGETVersions', $URLVars);
  	return $this->RequestGET($uri);
  }

	public function getViewVersion($viewZUID, $versionNumber)
	{
		$URLVars = array('VIEW_ZUID' => $viewZUID, 'VERSION_NUMBER' => $versionNumber, );
  	$uri = $this->makeInstanceAPIURL('viewsGETVersion', $URLVars);
  	return $this->RequestGET($uri);
	}

	public function createView($ViewData)
	{
  	$uri = $this->makeInstanceAPIURL('viewsPOST');
  	return $this->RequestPOST($uri, $ViewData);
  }

	public function updateView($viewZUID, $ViewData)
	{
		return $this->saveView($viewZUID, $ViewData);
	}

	public function saveView($viewZUID, $ViewData)
	{
		$URLVars = array('VIEW_ZUID' => $viewZUID, );
  	$uri = $this->makeInstanceAPIURL('viewsPUT', $URLVars);
  	return $this->RequestPUT($uri, $ViewData);
  }

	public function saveAndPublishView($viewZUID, $ItemData)
	{
		$URLVars = array('VIEW_ZUID' => $viewZUID, );
  	$uri = $this->makeInstanceAPIURL('viewsPUTPublish', $URLVars);
  	return $this->RequestPUT($uri, $ItemData);
  }

	public function publishView($viewZUID, $versionNumber)
	{
		$URLVars = array('VIEW_ZUID' => $viewZUID, 'VERSION_NUMBER' => $versionNumber, );
  	$uri = $this->makeInstanceAPIURL('viewsPublish', $URLVars);
  	$PublishData = array();
  	return $this->RequestPOST($uri, $PublishData);
  }


## ------------------------------------------------------
#mark Scripts

	public function getScripts()
	{
  	$uri = $this->makeInstanceAPIURL('scriptsGETAll');
  	return $this->RequestGET($uri);
  }

	public function getScript($scriptZUID)
	{
		$URLVars = array('SCRIPT_ZUID' => $scriptZUID, );
  	$uri = $this->makeInstanceAPIURL('scriptsGET', $URLVars);
  	return $this->RequestGET($uri);
  }

	public function getScriptVersions($scriptZUID)
	{
		$URLVars = array('SCRIPT_ZUID' => $scriptZUID, );
  	$uri = $this->makeInstanceAPIURL('scriptsGETVersions', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function getScriptVersion($scriptZUID, $versionNumber)
	{
		$URLVars = array('SCRIPT_ZUID' => $scriptZUID, 'VERSION_NUMBER' => $versionNumber, );
  	$uri = $this->makeInstanceAPIURL('scriptsGETVersion', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function saveScript($scriptZUID, $ScriptData)
	{
		$URLVars = array('SCRIPT_ZUID' => $scriptZUID, );
  	$uri = $this->makeInstanceAPIURL('scriptsPUT', $URLVars);
  	return $this->RequestPUT($uri, $ScriptData);
  }


	public function saveAndPublishScript($scriptZUID, $ScriptData)
	{
		$URLVars = array('SCRIPT_ZUID' => $scriptZUID, );
  	$uri = $this->makeInstanceAPIURL('scriptsPUTPublish', $URLVars);
  	return $this->RequestPUT($uri, $ScriptData);
  }
  

	public function createScript($ScriptData)
	{
	 	$uri = $this->makeInstanceAPIURL('scriptsPOST');
  	return $this->RequestPOST($uri, $ScriptData);
  }



## ------------------------------------------------------
#mark Stylesheets

	protected static $SupportedSSTYpes = array( "text/css", "text/less", "text/scss", "text/sass", );
##	validateStylesheet
##				https://github.com/zesty-io/node-sdk/blob/master/src/services/instance/stylesheets/index.js


	public function getStylesheets()
	{
  	$uri = $this->makeInstanceAPIURL('stylesheetsGETAll');
  	return $this->RequestGET($uri);
  }

	public function getStylesheet($stylesheetZUID)
	{
		$URLVars = array('STYLESHEET_ZUID' => $stylesheetZUID, );
  	$uri = $this->makeInstanceAPIURL('stylesheetsGET', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function getStylesheetVersions($stylesheetZUID)
	{
		$URLVars = array('STYLESHEET_ZUID' => $stylesheetZUID, );
  	$uri = $this->makeInstanceAPIURL('stylesheetsGETVersions', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function getStylesheetVersion($stylesheetZUID, $versionNumber)
	{
		$URLVars = array('STYLESHEET_ZUID' => $stylesheetZUID, 'VERSION_NUMBER' => $versionNumber, );
  	$uri = $this->makeInstanceAPIURL('stylesheetsGETVersion', $URLVars);
  	return $this->RequestGET($uri);
  }

	public function saveStylesheet($stylesheetZUID, $StylesheetData)
	{
		$URLVars = array('STYLESHEET_ZUID' => $stylesheetZUID, );
  	$uri = $this->makeInstanceAPIURL('scriptsPUT', $URLVars);
  	return $this->RequestPUT($uri, $StylesheetData);
  }


	public function saveAndPublishStylesheet($stylesheetZUID, $StylesheetData)
	{
		$URLVars = array('STYLESHEET_ZUID' => $stylesheetZUID, );
  	$uri = $this->makeInstanceAPIURL('stylesheetsPUTPublish', $URLVars);
  	return $this->RequestPUT($uri, $StylesheetData);
  }


	public function createStylesheet($StylesheetData)
	{
	 	$uri = $this->makeInstanceAPIURL('stylesheetsPOST');
  	return $this->RequestPOST($uri, $StylesheetData);
  }

	public function createStylesheetVariable($StylesheetVarData)
	{
	 	$uri = $this->makeInstanceAPIURL('stylesheetsVarPOST');
  	return $this->RequestPOST($uri, $StylesheetVarData);
  }




## ------------------------------------------------------
#mark Instances Global Items

	public function makeInstanceAPIURL($uri_key, $URLVars=array())
	{
  	$uri = $this->makeAPIURL($uri_key, 'instances');
  	if (!empty($URLVars))					$uri = str_replace(array_keys($URLVars), $URLVars, $uri);
		return $uri;
	}

	protected $siteId = false;

	public function getSiteId()
	{
    if ($this->siteId)				return $this->siteId;
		$InstanceResp = $this->getInstance();
		if (false === $InstanceResp)			return false;
    $this->siteId = $InstanceResp['data']['ID'];
    return $this->siteId;
  }

  public function getInstance()
  {
  	$uri = $this->makeAPIURL('instanceGET', 'accounts');
  	return $this->RequestGET($uri);
  }

  public function getInstances()
  {
  	$uri = $this->makeAPIURL('instancesBASE', 'accounts');
  	return $this->RequestGET($uri);
  }

	public function createInstance($InstanceData)
	{
  	$uri = $this->makeAPIURL('instancesBASE', 'accounts');
  	return $this->RequestPOST($uri, $InstanceData);
  }


  public function getSiteHead()
  {
	 	$uri = $this->makeInstanceAPIURL('siteHeadGET');
  	return $this->RequestGET($uri);
  }

  public function getNav()
  {
	 	$uri = $this->makeInstanceAPIURL('navGET');
  	return $this->RequestGET($uri);
  }

  public function search($searchTerm)
  {
		$URLVars = array('SEARCH_TERM' => $searchTerm, );
  	$uri = $this->makeInstanceAPIURL('searchGET', $URLVars);
  	return $this->RequestGET($uri);
  }

  public function getInstanceUsers($searchTerm)
  {
  	$uri = $this->makeAPIURL('instanceUsersGET', 'accounts');
  	return $this->RequestGET($uri);
  }

  public function getSettings($searchTerm)
  {
  	$uri = $this->makeInstanceAPIURL('settingsGETAll');
  	return $this->RequestGET($uri);
  }

  public function getSetting($settingsId)
  {
		$URLVars = array('SETTINGS_ID' => $settingsId, );
  	$uri = $this->makeInstanceAPIURL('settingsGET');
  	return $this->RequestGET($uri);
  }

	public function createSetting($SettingData)
	{
  	$uri = $this->makeInstanceAPIURL('settingsPOST');
  	return $this->RequestPOST($uri, $SettingData);
  }




	public function createHeadTag($modelZUID, $TagData)
	{
  	$uri = $this->makeInstanceAPIURL('headTagsPOST');
  	return $this->RequestPOST($uri, $TagData);
  }

	public function saveHeadTag($headTagZUID, $TagData)
	{
		$URLVars = array('HEADTAG_ZUID' => $headTagZUID, );
  	$uri = $this->makeInstanceAPIURL('headTagsPUT', $URLVars);
  	return $this->RequestPUT($uri, $TagData);
  }

	public function getHeadTags()
	{
  	$uri = $this->makeInstanceAPIURL('headTagsGETAll');
  	return $this->RequestGET($uri);
  }


	public function getHeadTag($headTagZUID)
	{
		$URLVars = array('HEADTAG_ZUID' => $headTagZUID, );
  	$uri = $this->makeInstanceAPIURL('headTagsGET', $URLVars);
  	return $this->RequestGET($uri);
  }

	public function deleteHeadTag($headTagZUID)
	{
		$URLVars = array('HEADTAG_ZUID' => $headTagZUID, );
  	$uri = $this->makeInstanceAPIURL('headTagsDELETE');
  	return $this->RequestDELETE($uri);
  }


	public function getAuditTrailEntries()
	{
  	$uri = $this->makeInstanceAPIURL('auditsGETAll');
  	return $this->RequestGET($uri);
  }

	public function getAuditTrailEntry($auditTrailEntryZUID)
	{
		$URLVars = array('AUDIT_ZUID' => $auditTrailEntryZUID, );
  	$uri = $this->makeInstanceAPIURL('auditsGET', $URLVars);
  	return $this->RequestGET($uri);
  }

##	$SearchParams should be a KVHash of search Object names => values.
	public function searchAuditTrailEntries($SearchParams=array())
	{
    // Object keys can be:
    // order
    // dir
    // start_date
    // end_date
    // limit
    // page
    // action
    // affectedZUID
    // userZUID

		$RequestParams = array();
		foreach($SearchParams as $paramName => $searchVal)
		{
			$RequestParams[] = $paramName .'='. $searchVal;
    }

// urlencode() ????????
		$URLVars = array('AUDIT_SEARCH_PARAMS' => implode('&', $RequestParams), );
  	$uri = $this->makeInstanceAPIURL('auditsGETParams', $URLVars);
  	return $this->RequestGET($uri);
  }		//	searchAuditTrailEntries



## ------------------------------------------------------
#mark Media

	public function makeMediaAPIURL($uri_key, $URLVars=array())
	{
  	$uri = $this->makeAPIURL($uri_key, 'media');
  	if (!empty($URLVars))					$uri = str_replace(array_keys($URLVars), $URLVars, $uri);
		return $uri;
	}

	public function getMediaBins()
	{
		$siteId = $this->getSiteId();
		$URLVars = array('SITE_ID' => $siteId, );
  	$uri = $this->makeMediaAPIURL('binsGETAll', $URLVars);
  	return $this->RequestGET($uri);
  }

	public function getMediaBin($mediaBinZUID)
	{
		$URLVars = array('BIN_ZUID' => $mediaBinZUID, );
  	$uri = $this->makeMediaAPIURL('binsGET', $URLVars);
  	return $this->RequestGET($uri);
  }

	public function updateMediaBin($mediaBinZUID, $BinData)
	{
		$URLVars = array('BIN_ZUID' => $mediaBinZUID, );
  	$uri = $this->makeMediaAPIURL('binsPATCH', $URLVars);
  	return $this->FormRequestPATCH($uri, $BinData);
  }

	public function getMediaFiles($mediaBinZUID)
	{
		$URLVars = array('BIN_ZUID' => $mediaBinZUID, );
  	$uri = $this->makeMediaAPIURL('filesGETAll', $URLVars);
  	return $this->RequestGET($uri);
  }

	public function getMediaFile($fileZUID)
	{
		$URLVars = array('FILE_ZUID' => $fileZUID, );
  	$uri = $this->makeMediaAPIURL('filesGET', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function createMediaFile($mediaBinZUID, $groupZUID, $fileName, $title, $contentType, $filePath )
	{
		if (!is_readable($filePath))		return $this->reportError(__METHOD__ ." - file to upload is not readable: $filePath");
		$MediaBin = $this->getMediaBin($mediaBinZUID);
		if (!$MediaBin['resp_status'])					return false;
		$MediaBin = $MediaBin['data'][0];
		
		$URLVars = array('STORAGE_DRIVER' => $MediaBin['storage_driver'], 'STORAGE_NAME' => $MediaBin['storage_name'], );
  	$uri = $this->makeMediaAPIURL('filesPOST', $URLVars);

		$MFileMeta = array('bin_id' => $mediaBinZUID, 'group_id' => $groupZUID, 'title' => $title, 'file' => array(), );
		$MFileMeta['file']['options'] = array('filename' => $fileName, 'contentType' => $contentType, );
		$MFileMeta['file']['value'] = file_get_contents($filePath);

	 	$uri = $this->makeMediaAPIURL('filesPOST');
  	return $this->FormRequestPOST($uri, $MFileMeta);
        // NOTE: user_id - seems to be optional, didn't add it because
        // it adds another API call overhead for no benefit?
  }

  // payload: filename, title, group_id
	public function updateMediaFile($fileZUID, $FileData)
	{
		$URLVars = array('FILE_ZUID' => $fileZUID, );
  	$uri = $this->makeMediaAPIURL('filesDELETE', $URLVars);
  	return $this->FormRequestPATCH($uri, $FileData);
  }

	public function deleteMediaFile($fileZUID)
	{
		$URLVars = array('FILE_ZUID' => $fileZUID, );
  	$uri = $this->makeMediaAPIURL('filesDELETE', $URLVars);
  	return $this->RequestDELETE($uri);
  }

	public function getMediaGroups($mediaBinZUID)
	{
		$URLVars = array('BIN_ZUID' => $mediaBinZUID, );
  	$uri = $this->makeMediaAPIURL('groupsGETAll', $URLVars);
  	return $this->RequestGET($uri);
  }


	public function getMediaGroup($groupZUID)
	{
		$URLVars = array('GROUP_ZUID' => $groupZUID, );
  	$uri = $this->makeMediaAPIURL('groupsGET', $URLVars);
  	return $this->RequestGET($uri);
  }

  // payload: bin_id, group_id, name
	public function createMediaGroup($MGroupData)
	{
  	$uri = $this->makeMediaAPIURL('groupsPOST');
  	return $this->FormRequestPOST($uri, $MGroupData);
  }


  // payload: filename, title, group_id
	public function updateMediaGroup($groupZUID, $MGroupData)
	{
#    if (!$groupZUID) {
#      throw new Error("Missing required `groupZUID` argument");
#    }
#    if (!$MGroupData.['name']) {
#      throw new Error("Missing required `payload.name` argument");
#    }
		$URLVars = array('GROUP_ZUID' => $groupZUID, );
  	$uri = $this->makeMediaAPIURL('groupsPATCH', $URLVars);
  	return $this->FormRequestPATCH($uri, $MGroupData);
  }
##	  bin_id: payload.binZUID,          group_id: payload.groupZUID,

	public function deleteMediaGroup($groupZUID)
	{
		$URLVars = array('GROUP_ZUID' => $groupZUID, );
  	$uri = $this->makeMediaAPIURL('groupsDELETE', $URLVars);
  	return $this->RequestDELETE($uri);
  }





## ------------------------------------------------------
#mark Logging

  protected function reportError($err)
  {    // Don't log null messages
  	$this->lastError = $err;
    if ( !empty($err) )			error_log($err);
    return false;
  }


	public $lastError = '';
  protected function logError($err)
  {    // Don't log null messages
  	$this->lastError = $err;
    if ( $this->logErrors && !empty($err) )			error_log($err);
    return $this;
  }

	public $lastResponse = false;
  protected function logResponse($Response)
  {
  	$this->lastResponse = $Response;
    if ( $this->logResponses && !empty($Response) )			error_log(print_r($Response, true));
    return $this;
  }
	

## ------------------------------------------------------
#mark Authentication

	public function AuthToken()			{			return $this->authToken;		}


## returns either a TRUE or a error KVHash.
## if auth succeeds, the Bearer token is stored in the authToken inst-var.
	public function Auth_Login($email, $pwd)
	{
		if (empty($email))				return $this->reportError(__METHOD__ .' Missing required element: email');
		if (empty($pwd))				return $this->reportError(__METHOD__ .' Missing required element: password');
		$url = $this->authURL .'/login';
		$Data = array('email' => $email, 'password' => $pwd, );
		$AuthResp = ZestyIO_Util::FormRequestPOST($url, $Data);
		if (!$AuthResp['req_status'])				return array('code' => -1, 'message' => 'Unexpected error. '. $AuthResp['resp_msg'], 'response' => $AuthResp, );
		if (!$AuthResp['resp_status'])				return array('code' => $AuthResp['resp_code'], 'message' => $AuthResp['resp_data']['message'], 'response' => $AuthResp, );

		$this->authToken = $AuthResp['resp_data']['meta']['token'];
		return true;
	}

#	returns either a boolean or an error KVHash
	public function Auth_VerifyToken($tokenToVerify=false)
	{
		$url = $this->authURL .'/verify';
		$Options = $this->ReqAuthOptions($tokenToVerify);
		$TVerifyResp = ZestyIO_Util::JSONGet($url, $Options);
		if (!$TVerifyResp['req_status'])				return array('code' => -1, 'message' => 'Unexpected error. '. $TVerifyResp['resp_msg'], 'response' => $TVerifyResp, );
		if ($TVerifyResp['resp_status'])				return true;

		if (false === $tokenToVerify)				$this->authToken = false;		// If our own stored token verification request returns false, then burn the token to the ground.
		return false;
	}

	protected function ReqAuthOptions($tokenOverride=false)
	{
		if (false !== $tokenOverride)				return array('auth_token' => $this->tokenOverride, );
		if (false === $this->authToken)				return array();
		return array('auth_token' => $this->authToken, );
	}

	public function Authed()
	{
		return (false !== $this->authToken);
	}


## ----------------------------------------------------
#mark Sites Service

	public function makeSitesServiceAPIURL($uri_key, $URLVars=array())
	{
  	$uri = $this->makeAPIURL($uri_key, 'sites-service');
		return str_replace(array_keys($URLVars), $URLVars, $uri);
	}


	protected function sitesServiceResponseFormatter($Response)
	{
		return array(
			'_meta' => array('timestamp' => gmdate('c', intval($Response['req_start_time'])), 'totalResults' => 1, 'start' => 0, 'offset' => 0, 'limit' => 1, ),
			'message' => $Response['resp_msg'],
			'data' => $Response['data'], 
			);
  }


## ----------------------------------------------------
#mark API Request VERBs

	public function RequestGET($uri, $ReqOptions=array())
	{
		if (false === $uri)					return false;
  	if (!$this->Authed())				return false;
		$ReqOptions += $this->ReqAuthOptions();
  	return ZestyIO_Util::JSONGet($uri, $ReqOptions);
  }
	
	public function RequestPUT($uri, $PUTData, $ReqOptions=array())
	{
		if (false === $uri)					return false;
  	if (!$this->Authed())				return false;
		$ReqOptions += $this->ReqAuthOptions();
  	return ZestyIO_Util::JSONPut($uri, $PUTData, $ReqOptions);
  }

	public function RequestPOST($uri, $POSTData, $ReqOptions=array())
	{
		if (false === $uri)					return false;
  	if (!$this->Authed())				return false;
		$ReqOptions += $this->ReqAuthOptions();
		$ReqOptions['success_code'] = '201';
  	return ZestyIO_Util::JSONPost($uri, $POSTData, $ReqOptions);
  }

	public function RequestPATCH($uri, $PATCHData, $ReqOptions=array())
	{
		if (false === $uri)					return false;
  	if (!$this->Authed())				return false;
		$ReqOptions += $this->ReqAuthOptions();
  	return ZestyIO_Util::JSONPatch($uri, $PATCHData, $ReqOptions);
  }



	public function FormRequestPOST($uri, $POSTData, $ReqOptions=array())
	{
		if (false === $uri)					return false;
  	if (!$this->Authed())				return false;
		$ReqOptions += $this->ReqAuthOptions();
		$ReqOptions['success_code'] = '201';
  	return ZestyIO_Util::HTTPFormPost($uri, $POSTData, $ReqOptions);
  }

	public function FormRequestPATCH($uri, $PATCHData, $ReqOptions=array())
	{
		if (false === $uri)					return false;
  	if (!$this->Authed())				return false;
		$ReqOptions += $this->ReqAuthOptions();
  	return ZestyIO_Util::HTTPFormPatch($uri, $PATCHData, $ReqOptions);
  }

	public function RequestDELETE($uri, $ReqOptions=array())
	{
		if (false === $uri)					return false;
  	if (!$this->Authed())				return false;
		$ReqOptions += $this->ReqAuthOptions();
  	return ZestyIO_Util::HTTPDelete($uri, $ReqOptions);
  }
	


## ----------------------------------------------------
#mark API URL Endpoint Util & Definitions

## Included for compatibility.
  public function buildAPIURL($uri, $api='instances')
  {
    switch($api)
    {
      case 'accounts':
        return $this->accountsAPIURL . $uri;
      case 'instances':
        return $this->instancesAPIURL . $uri;
      case 'sites-service':
        return $this->sitesServiceURL . $uri;
      case 'media':
        return $this->mediaAPIURL . $uri;
      default:
        return '';
    }
  }


## This is a better version of buildAPIURL();
  public function makeAPIURL($uriKey, $api='instances')
  {
    switch($api)
    {
      case 'accounts':
      	if (!key_exists($uriKey, $this->accountsAPIEndpoints))					return false;
        return $this->accountsAPIURL . $this->accountsAPIEndpoints[$uriKey];
      case 'instances':
      	if (!key_exists($uriKey, $this->instancesAPIEndpoints))					return false;
        return $this->instancesAPIURL . $this->instancesAPIEndpoints[$uriKey];
      case 'sites-service':
      	if (!key_exists($uriKey, $this->sitesServiceEndpoints))					return false;
        return $this->sitesServiceURL . $this->sitesServiceEndpoints[$uriKey];
      case 'media':
      	if (!key_exists($uriKey, $this->mediaAPIEndpoints))					return false;
        return $this->mediaAPIURL . $this->mediaAPIEndpoints[$uriKey];
      default:
        return '';
    }
  }



	protected $instancesAPIEndpoints = array(
      'modelsGETAll' => '/content/models',
      'modelsGET' => '/content/models/MODEL_ZUID',
      'modelsPOST' => '/content/models',
      'fieldsGETAll' => '/content/models/MODEL_ZUID/fields',
      'fieldGET' => '/content/models/MODEL_ZUID/fields/FIELD_ZUID',
      'fieldPOST' => '/content/models/MODEL_ZUID/fields',
      'itemsSEARCH' => '/search/items?q=SEARCH_TERM',
      'itemsGETAll' => '/content/models/MODEL_ZUID/items',
      'itemsPOST' => '/content/models/MODEL_ZUID/items',
      'itemsGETPaged' => '/content/models/MODEL_ZUID/items?page=PAGE&limit=LIMIT&lang=LANG&_active=ACTIVE',
      'itemsGET' => '/content/models/MODEL_ZUID/items/ITEM_ZUID',
      'itemsDELETE' => '/content/models/MODEL_ZUID/items/ITEM_ZUID',
      'itemsGETPublishings' => '/content/models/MODEL_ZUID/items/ITEM_ZUID/publishings',
      'itemsGETPublishing' => '/content/models/MODEL_ZUID/items/ITEM_ZUID/publishings/PUBLISHING_ZUID',
      'itemsGETVersions' => '/content/models/MODEL_ZUID/items/ITEM_ZUID/versions',
      'itemsGETVersion' => '/content/models/MODEL_ZUID/items/ITEM_ZUID/versions/VERSION_NUMBER',
      'itemsPUT' => '/content/models/MODEL_ZUID/items/ITEM_ZUID',
      'viewsGETAll' => '/web/views',
      'viewsGET' => '/web/views/VIEW_ZUID',
      'viewsGETVersions' => '/web/views/VIEW_ZUID/versions',
      'viewsGETVersion' => '/web/views/VIEW_ZUID/versions/VERSION_NUMBER',
      'viewsPOST' => '/web/views',
      'viewsPUT' => '/web/views/VIEW_ZUID',
      'viewsPUTPublish' => '/web/views/VIEW_ZUID?action=publish',
      'viewsPublish' => '/web/views/VIEW_ZUID/versions/VERSION_NUMBER',
      'settingsGETAll' => '/env/settings',
      'settingsGET' => '/env/settings/SETTINGS_ID',
      'settingsPOST' => '/env/settings',
      'stylesheetsGETAll' => '/web/stylesheets',
      'stylesheetsGET' => '/web/stylesheets/STYLESHEET_ZUID',
      'stylesheetsGETVersions' => '/web/stylesheets/STYLESHEET_ZUID/versions',
      'stylesheetsGETVersion' => '/web/stylesheets/STYLESHEET_ZUID/versions/VERSION_NUMBER',
      'stylesheetsPOST' => '/web/stylesheets',
      'stylesheetsPUT' => '/web/stylesheets/STYLESHEET_ZUID',
      'stylesheetsPUTPublish' => '/web/stylesheets/STYLESHEET_ZUID?action=publish',
      'stylesheetsVarPOST' => '/web/stylesheets/variables',
      'scriptsGETAll' => '/web/scripts',
      'scriptsGET' => '/web/scripts/SCRIPT_ZUID',
      'scriptsGETVersions' => '/web/scripts/SCRIPT_ZUID/versions',
      'scriptsGETVersion' => '/web/scripts/SCRIPT_ZUID/versions/VERSION_NUMBER',
      'scriptsPOST' => '/web/scripts',
      'scriptsPUT' => '/web/scripts/SCRIPT_ZUID',
      'scriptsPUTPublish' => '/web/scripts/SCRIPT_ZUID?action=publish',
      'siteHeadGET' => '/web/headers',
      'navGET' => '/env/nav',
      'searchGET' => '/search/items?q=SEARCH_TERM', // Undocumented
      'headTagsGETAll' => '/web/headtags',
      'headTagsGET' => '/web/headtags/HEADTAG_ZUID',
      'headTagsDELETE' => '/web/headtags/HEADTAG_ZUID',
      'headTagsPUT' => '/web/headtags/HEADTAG_ZUID',
      'headTagsPOST' => '/web/headtags',
      'auditsGETAll' => '/env/audits',
      'auditsGET' => '/env/audits/AUDIT_ZUID',
      'auditsGETParams' => '/env/audits?AUDIT_SEARCH_PARAMS',
    );

    protected $accountsAPIEndpoints = array(
      'instancesBASE' => '/instances',
      'instanceGET' => '/instances/INSTANCE_ZUID',
      'instanceUsersGET' => '/instances/INSTANCE_ZUID/users/roles',
    );

    protected $sitesServiceEndpoints = array(
      'schedulePublishPOST' => '/content/items/ITEM_ZUID/publish-schedule',
      'scheduleUnpublishPATCH' => '/content/items/ITEM_ZUID/publish-schedule/PUBLISHING_ZUID',
      'itemsDELETE' => '/content/sets/MODEL_ZUID/items/ITEM_ZUID',
    );
  
    protected $mediaAPIEndpoints = array(
      'binsGETAll' => '/media-manager-service/site/SITE_ID/bins',
      'binsGET' => '/media-manager-service/bin/BIN_ZUID',
      'binsPATCH' => '/media-manager-service/bin/BIN_ZUID',
      'filesPOST' => '/media-storage-service/upload/STORAGE_DRIVER/STORAGE_NAME',
      'filesGET' => '/media-manager-service/file/FILE_ZUID',
      'filesGETAll' => '/media-manager-service/bin/BIN_ZUID/files',
      'filesPATCH' => '/media-manager-service/file/FILE_ZUID',
      'filesDELETE' => '/media-manager-service/file/FILE_ZUID',
      'groupsGET' => '/media-manager-service/group/GROUP_ZUID',
      'groupsGETAll' => '/media-manager-service/bin/BIN_ZUID/groups',
      'groupsPOST' => '/media-manager-service/group',
      'groupsPATCH' => '/media-manager-service/group/GROUP_ZUID',
      'groupsDELETE' => '/media-manager-service/group/GROUP_ZUID',
    );

}		//	ZestyIO_Instance





class ZestyIO_Util
{

	public static function HTTPRequest($url, $StreamOptions, $Options=array())
	{
		$StreamCtx = self::BuildContext($StreamOptions, $Options);

		if (key_exists('successCode', $Options))			$Options['success_code'] = $Options['successCode'];		// compatibility.
		$successCode = key_exists('success_code', $Options)	?	$Options['success_code'] : '200';

		$HTTPResponse = array('type' => $Options['type'], 'req_status' => true, 'requested_url' => $url, 
															'req_start_time' => microtime(true), 'resp_data_status' => true, );
		$HTTPResponse['data'] = file_get_contents($url, false, $StreamCtx);
		$HTTPResponse['req_elapsed'] = microtime(true) - $HTTPResponse['req_start_time'];
		if (false === $HTTPResponse['data'])
		{
			$HTTPResponse['req_status'] = false;
			$HTTPResponse['data'] = false;
		}
		if (!empty($http_response_header))
		{
			list($HTTPResult, $HTTPResponse['resp_hdrs']) = self::ParseHTTPHeaders($http_response_header);
			if ($successCode != $HTTPResult['resp_code'])			$HTTPResult['resp_status'] = false;
			$HTTPResponse += $HTTPResult;
		}
		$this->logResponse($HTTPResponse);

		if (key_exists('response_formatter', $Options) && is_callable($Options['response_formatter']) )
		{
			return call_user_func($Options['response_formatter'], $HTTPResponse);
		}

		return $HTTPResponse;
	}		//	HTTPRequest

	public static function HTTPDelete($url, $Options=array())
	{
		$StreamOptions = array('method' => 'DELETE', 'ignore_errors' => true, );
		$Options['type'] = 'form';
		return self::HTTPRequest($url, $StreamOptions, $Options);
	}

	public static function HTTPFormPost($url, $Payload, $Options=array())
	{
		$StreamOptions = array('method' => 'POST', 'content' => $Payload, 'ignore_errors' => true, );
		$Options['type'] = 'form';
		return self::HTTPRequest($url, $StreamOptions, $Options);
	}

	public static function HTTPFormPatch($url, $Payload, $Options=array())
	{
		$StreamOptions = array('method' => 'PATCH', 'content' => $Payload, 'ignore_errors' => true, );
		$Options['type'] = 'form';
		return self::HTTPRequest($url, $StreamOptions, $Options);
	}



### -----------------------------------------------------------------------
## JSON Methods

	public static function JSONRequest($url, $StreamOptions, $Options=array())
	{
		$Options['content-type'] = 'application/json';
		$Options['type'] = 'json';
		$HTTPResponse = self::HTTPRequest($url, $StreamOptions, $Options);
		if (self::RespIsJSON($HTTPResponse))
		{
			$Tmp = json_decode($HTTPResponse['data'], true);
			if (false === $Tmp)
			{
				$HTTPResponse['resp_data_status'] = false;
				$HTTPResponse['resp_error'] = json_last_error_msg();
        $his->logError($StreamOptions['method'] ." to $url - JSON response cannot be decoded:". $HTTPResponse['resp_error']);
			}
			else 			$HTTPResponse['data'] = $Tmp;
		}
		return $HTTPResponse;
	}

	public static function JSONGet($url, $Options=array())
	{
		$StreamOptions = array('method' => 'GET', 'ignore_errors' => true, );
		return self::JSONRequest($url, $StreamOptions, $Options);
	}

	public static function JSONPut($url, $Payload, $Options=array())
	{
		if (is_array($Payload))				$Payload = json_encode($Payload);
		$StreamOptions = array('method' => 'PUT', 'content' => $Payload, 'ignore_errors' => true, );
		return self::JSONRequest($url, $StreamOptions, $Options);
	}

	public static function JSONPost($url, $Payload, $Options=array())
	{
		if (is_array($Payload))				$Payload = json_encode($Payload);
		$StreamOptions = array('method' => 'POST', 'content' => $Payload, 'ignore_errors' => true, );
		return self::JSONRequest($url, $StreamOptions, $Options);
	}

	public static function JSONPatch($url, $Payload, $Options=array())
	{
		if (is_array($Payload))				$Payload = json_encode($Payload);
		$StreamOptions = array('method' => 'PATCH', 'content' => $Payload, 'ignore_errors' => true, );
		return self::JSONRequest($url, $StreamOptions, $Options);
	}




	public static function RespIsJSON($HTTPResponse)
	{		##	    'Content-Type' => 'application/json;odata.metadata=minimal;odata.streaming=false;IEEE754Compatible=false; charset=utf-8',
		if (empty($HTTPResponse['data']))				return false;		// can't be JSON if there is nothing returned.
		$TmpHdrs = array_change_key_case($HTTPResponse['resp_hdrs'], CASE_LOWER);
		if (boolval(stristr($TmpHdrs['content-type'], 'application/json')))			return true;
		return false;
	}



## Returns a Pair(HTTPResult, HTTPReceived Headers as KV Hash)
	public static function ParseHTTPHeaders($httpHeadersBlob)
	{
		$HTTPResult = array('resp_raw' => '', 'resp_status' => true, 'resp_code' => '', 'resp_msg' => '', 'resp_version' => '', 'resp_code_class' => '', );
		$Headers = array();
		$httpHeadersBlob = trim($httpHeadersBlob);
		foreach(explode("\n", $httpHeadersBlob) as $i => $loopHdrLine)
		{
			$loopHdrLine = trim($loopHdrLine);
			if ( (0 == $i) && ('HTTP' == substr($loopHdrLine, 0, 4)))				//		HTTP/1.1 200 OK
			{
				$HTTPResult['resp_raw'] = $loopHdrLine;
				list($d1, $HTTPResult['resp_code'], $HTTPResult['resp_msg']) = explode(' ', $loopHdrLine, 3);
				$HTTPResult['resp_msg'] = trim($HTTPResult['resp_msg']);
				list($d1, $HTTPResult['resp_version']) = explode('/', $d1);
				$HTTPResult['resp_code_class'] = substr($HTTPResult['resp_code'], 0, 1);
			}
			else
			{
				list($hkey, $hval) = explode(':', $loopHdrLine, 2);
				$Headers[$hkey] = trim($hval);
			}
		}
		
		return array($HTTPResult, $Headers);
	}		//	ParseHTTPHeaders



	public static function BuildContext($StreamOptions, $Extras=array())
	{
		if( !key_exists('user_agent', $StreamOptions))				$StreamOptions['user_agent'] = 'PHP/ZestyIO_API';
		if( !key_exists('max_redirects', $StreamOptions))			$StreamOptions['max_redirects'] = 2;

		$Extras = array_change_key_case($Extras, CASE_LOWER);

		$Headers = key_exists('header', $StreamOptions) ? $StreamOptions['header'] : array();
		if (!is_array($Headers))		$Headers = explode("\r\n", trim($Headers));

		if (key_exists('content-type', $Extras))		$Headers[] = 'Content-type: '. $Extras['content-type'];
		$usesXAuthHeader = key_exists('usesXAuthHeader', $Extras) ? boolval($Extras['usesXAuthHeader']) : false;

		$lowerHeadersBlob = "\n". strtolower(implode("\n", $Headers));
		if (!strstr($lowerHeadersBlob, "\n".'accept-language:'))			$Headers[] = 'Accept-language: en';
		if (!strstr($lowerHeadersBlob, "\n".'accept-charset:'))				$Headers[] = 'Accept-Charset: utf-8,*';

		if (key_exists('referer', $Extras))		$Headers[] = 'Referer: '. $Extras['referer'];
		if (key_exists('auth', $Extras))
		{
			if (key_exists('username', $Extras))
			{
				$encodedUserPass = base64_encode($Extras['username'] .':'. $Extras['password']);
				$Headers[] = "Authorization: Basic $encodedUserPass";
			}
		}
		elseif (key_exists('auth_token', $Extras))
		{
			$aToken = trim($Extras['auth_token']);
			$Headers[] = ($usesXAuthHeader)	?	"X-Auth: $aToken"	:	"Authorization: Bearer $aToken";			// DEPRECATED
		}
		$StreamOptions['header'] = $Headers;

		$context = stream_context_create(array('http' => $StreamOptions));
		return $context;
	}

	
#	protected static function getJSONByCurl($query)
#	{
#		$ch = curl_init();
#		$options = array(
#				CURLOPT_TIMEOUT => $this->timeout,
#				CURLOPT_URL => $query,
#				CURLOPT_HEADER => true,
#				CURLOPT_RETURNTRANSFER => 1,
#		);
#		curl_setopt_array($ch, $options);
#		$result = curl_exec($ch);
#		if (false === $result)			return false;
#		
#		$Response = array('http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'request_lib' => 'curl');
#		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
#		curl_close($ch);
#		$Response['resp_hdrs'] = substr($result, 0, $header_size);
#		$Response['data'] = $this->rawResponse = substr($result, $header_size );
#		return $Response;		// we need to possibly capture RATELIMIT headers.
#	}

}		//	ZestyIO_Util


