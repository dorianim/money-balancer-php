<?php

define('L_EXEC', true);
require_once './theme.php';
require_once './strings.php';
require_once './storageHelper.php';
require_once './version.php';

class SyncedTimer
{

    private $_serverConfig = array(
        "hideIndexPhp" => false
    );
    private $_themeConfig = array();
    private $_translations;
    private $_basepath;
    private $_resourcePath;
    private $_path;
    private $_dataDir = "/config/data";
    private $_storageHelper;

    public function __construct($translations)
    {
        session_start();
        $this->_translations = $translations;

        $this->_storageHelper = new StorageHelper($this->_dataDir);

        $this->_calculateBasepath();
        $this->_themeConfig["basePath"] = $this->_basepath;
        $this->_themeConfig["mainIcon"] = $this->_resourcePath . "IconSmallSquareOutline.png";
        $this->_theme = new LandingpageTheme($this->_themeConfig, $this->_storageHelper, $this->_translations);

        $this->_processRequest();
    }

    private function _processRequest()
    {
        $this->_loadUserFromHeaders();
        $this->_updatePermissions();
        $this->_checkPagePermissions();

        if ($this->_stringEndsWith($this->_path, "submit")) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->_redirect('/');
            }

            switch ($this->_path) {
                case '/balance/submit':
                    $this->_handleBalanceSubmit();
                    $this->_redirect('/balance');
                    break;

                default:
                    $this->_redirect("/");
                    break;
            }
        } else {
            $paramList = explode('/', ltrim($this->_path, '/'), 2);
            $endpoint = $paramList[0];
            $parameters = $paramList[1];

            $this->_theme->printPage($endpoint, $parameters);
        }

        unset($_SESSION['lastResult']);
    }

    private function _calculateBasepath()
    {
        if ($this->_serverConfig['hideIndexPhp']) {
            $this->_basepath = str_replace(basename($_SERVER["SCRIPT_NAME"]), '', $_SERVER['SCRIPT_NAME']);
            $this->_resourcePath = $this->_basepath;
        } else {
            $this->_basepath = $_SERVER["SCRIPT_NAME"];
            $this->_resourcePath = str_replace(basename($_SERVER["SCRIPT_NAME"]), '', $_SERVER['SCRIPT_NAME']);
        }

        $this->_basepath = rtrim($this->_basepath, "/ ");

        if (($this->_basepath !== '' && strpos($_SERVER['REQUEST_URI'], $this->_basepath) === false) || $_SERVER['REQUEST_URI'] === $this->_basepath)
            $this->_path = "/";
        else
            $this->_path = str_replace($this->_basepath, "", $_SERVER['REQUEST_URI']);
    }

    private function _redirect($path)
    {
        header('Location: ' . $this->_basepath . $path);
        die();
    }

    //
    // - Page printer (theme)
    //

    private function _printJson($username)
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        $allUserdata = $this->_storageHelper->loadUserdata();
        if (array_key_exists($username, $allUserdata)) {
            $userdata = $allUserdata[$username];
            die(json_encode(array(
                "status" => 200,
                "data" => array(
                    "time" => $userdata["time"],
                    "startedAt" => $userdata["startedAt"],
                    "header" => $userdata["header"]
                )
            )));
        } else {
            http_response_code(404);
            die(json_encode(array(
                "status" => 404,
                "data" => null
            )));
        }
    }

    // -----------------------
    // - Permission handlers -
    // -----------------------

    private function _loadUserFromHeaders()
    {
        if(!isset($_SERVER["HTTP_X_AUTHENTIK_USERNAME"])) {
            unset($_SESSION["auth"]);
            return;
        }

        $_SESSION["auth"]["loggedIn"] = true;
        $_SESSION["auth"]["username"] = $_SERVER["HTTP_X_AUTHENTIK_USERNAME"]   ;
    }

    private function _updatePermissions()
    {
        $_SESSION['permissions'][''] = false;
        $_SESSION['permissions']['balance'] = $this->_isUserAuthenticated();
        $_SESSION['permissions']['login'] = !$this->_isUserAuthenticated();
    }

    private function _checkPagePermissions()
    {
        $pageRedirectOnInsufficientPermissionsPriority = [
            0 => "balance",
            1 => "login"
        ];

        $page = explode("/", $this->_path)[1];

        if (!isset($_SESSION['permissions'][$page])) {
            $this->_redirect('/');
        } else if ($_SESSION['permissions'][$page] === false) {
            if (!$this->_isUserAuthenticated()) {
                $_SESSION['lastResult'] = 'loginRequired';
            }

            if ($this->_path === '/' || $this->_path === '') {
                // if the root is opened, do not throw an error!
                unset($_SESSION['lastResult']);
            }

            // redirect to the first page the user has access to
            foreach ($pageRedirectOnInsufficientPermissionsPriority as $page) {
                if ($_SESSION['permissions'][$page])
                    $this->_redirect("/" . $page);
            }

            die($this->_translations['results']['noPermissionToAnyPage']);
        }
    }

    // -------------------
    // - Submit handlers -
    // -------------------

    private function _handleBalanceSubmit()
    {
        $this->_storageHelper->addPurchase(
            "balance", 
            $_SESSION["auth"]["username"], 
            $_POST["name"], 
            $_POST["amount"], 
            time()
        );
        $_SESSION['lastResult'] = "purchaseAddedSuccessfully";
    }

    // ----------------------------
    // - General helper functions -
    // ----------------------------

    private function _isUserAuthenticated()
    {
        $authenticated =
            isset($_SESSION['auth'])
            && isset($_SESSION['auth']['loggedIn'])
            && $_SESSION['auth']['loggedIn'] === true
            && isset($_SESSION['auth']['username']);

        if (!$authenticated && isset($_SESSION['auth'])) {
            unset($_SESSION['auth']);
        }

        return $authenticated;
    }

    private function _stringStartsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return substr($haystack, 0, $length) === $needle;
    }

    private function _stringEndsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
}

new SyncedTimer($translations);
