<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Installer
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Abstract adapter for the installer.
 *
 * @package     Joomla.Libraries
 * @subpackage  Installer
 * @since       3.1
 */
abstract class JInstallerAdapter
{
	/**
	 * ID for the currently installed extension if present
	 *
	 * @var    integer
	 * @since  3.1
	 *
	 * @todo   Use $this->extension->extension_id instead.
	 */
	protected $currentExtensionId = null;

	/**
	 * Database driver
	 *
	 * @var    JDatabaseDriver
	 * @since  3.1
	 */
	protected $db = null;

	/**
	 * The unique identifier for the extension (e.g. mod_login)
	 *
	 * @var    string
	 * @since  3.1
	 *
	 * @todo   Use $this->extension->element instead and remove this one.
	 */
	protected $element = null;

	/**
	 * Extension object.
	 *
	 * @var    JTableExtension
	 * @since  3.1
	 * */
	protected $extension = null;

	/**
	 * Messages rendered by custom scripts
	 *
	 * @var    string
	 * @since  3.1
	 */
	protected $extensionMessage = '';

	/**
	 * Copy of the XML manifest file.
	 *
	 * @var    string
	 * @since  3.1
	 */
	protected $manifest = null;

	/**
	 * A path to the PHP file that the scriptfile declaration in
	 * the manifest refers to.
	 *
	 * @var    string
	 * @since  3.1
	 * */
	protected $manifest_script = null;

	/**
	 * Name of the extension
	 *
	 * @var    string
	 * @since  3.1
	 *
	 * @todo   Use $this->extension->name instead and remove this one.
	 */
	protected $name = null;

	/**
	 * JInstaller instance accessible from the adapters
	 *
	 * @var    JInstaller
	 * @since  3.1
	 */
	protected $parent = null;

	/**
	 * Install function routing
	 *
	 * @var    string
	 * @since  3.1
	 */
	protected $route = 'install';

	/**
	 * @var    string
	 * @since  3.1
	 */
	protected $scriptElement = null;

	/**
	 * The type of adapter in use
	 *
	 * @var    string
	 * @since  3.1
	 */
	protected $type;

	/**
	 * Constructor
	 *
	 * @param   JInstaller       $parent   Parent object
	 * @param   JDatabaseDriver  $db       Database object
	 * @param   array            $options  Configuration Options
	 *
	 * @since   11.1
	 */
	public function __construct($parent, $db, $options = array())
	{
		// Set the parent
		$this->parent = $parent;

		// Set the database object
		$this->db = $db;

		// Set any options if present
		if (count($options) >= 1)
		{
			foreach ($options as $key => $value)
			{
				$this->$key = $value;
			}
		}

		// Find the manifest if not given as a configuration option
		if (!$this->manifest)
		{
			$this->manifest = $this->findManifest();
		}

		// Set name and element
		// TODO: remove and use internally $this->extension instead.
		$this->name = $this->getName();
		$this->element = $this->getElement();

		// Get a generic JTableExtension instance for use if not already loaded
		if (!($this->extension instanceof JTable))
		{
			$this->extension = JTable::getInstance('extension');
			$this->extension->name = $this->getName();
			$this->extension->element = $this->getElement();
			// TODO: do we want to pre-fill other values as well?
		}
	}

	/**
	 * The magic set method is used to set a property to the object.
	 *
	 * @param   string  $name   The name of the property.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function __set($name, $value = null) {
		$this->set($name, $value);
	}

	/**
	 * The magic get method is used to get a property from the object.
	 *
	 * @param   string  $name  The name of the property.
	 *
	 * @return  mixed  The value of the property if set, null otherwise.
	 *
	 * @since   3.1
	 */
	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * The magic isset method is used to check the state of a property.
	 *
	 * @param   string  $name  The name of the property.
	 *
	 * @return  boolean  True if set, false otherwise.
	 *
	 * @since   3.1
	 */
	public function __isset($name) {
		return $this->get($name) !== null;
	}

	/**
	 * Set a property to the object.
	 *
	 * @param   string  $name   The name of the property.
	 * @param   mixed   $value  The value of the property.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function set($name, $value = null) {
		// TODO: We do not want extension scripts to change values from the variables, or do we?
		// TODO: raise exception?
	}

	/**
	 * Get a property from the object.
	 *
	 * @param   string  $name  The name of the property.
	 *
	 * @return  mixed  The value of the property if set, null otherwise.
	 *
	 * @since   3.1
	 */
	public function get($name) {
		switch ($name) {
			// Backwards compatibility: alias for $this->get('extension')->name
			case 'name' :
			// Backwards compatibility: alias for $this->get('extension')->element
			case 'element' :
				return $this->extension->$name;

			// Installation method/route.
			case 'route' :
			// Manifest script location.
			// TODO: do we need both source and target? What's the meaning of this field?
			case 'manifest_script' :
			// Database object.
			case 'db' :
			// Allow extension scripts to access and modify the manifest DOM.
			case 'manifest' :
			// Allow extension scripts to access and modify extension instance.
			case 'extension' :
				return $this->$name;

			// Deprecated exposed variables since 3.1 (to be removed in 4.0)
			// TODO: find out if there are more legacy variables to support.
			// TODO: check if we need to provide alternative methods for these.
			case 'parent' :
			case 'install_script' :
				return $this->$name;

			default :
				// Deprecated
				return isset($this->$name) ? $this->$name : null;
		}
		return null;
	}

	/**
	 * Load extension by element name.
	 *
	 * @param string $element
	 */
	protected function loadExtension($element)
	{
		$this->extension->load(array('element' => $element, 'type' => $this->type));
	}

	/**
	 * Method to check if the extension is already present in the database
	 *
	 * @return  void
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function checkExistingExtension()
	{
		try
		{
			$this->currentExtensionId = $this->extension->find(array('element' => $this->element, 'type' => $this->type));
		}
		catch (RuntimeException $e)
		{
			// Install failed, roll back changes
			throw new RuntimeException(
				JText::sprintf(
					'JLIB_INSTALLER_ABORT_PLG_INSTALL_ROLLBACK',
					JText::_('JLIB_INSTALLER_' . $this->route),
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Method to check if the extension is present in the filesystem, flags the route as update if so
	 *
	 * @return  void
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function checkExtensionInFilesystem()
	{
		if (file_exists($this->parent->getPath('extension_root')) && (!$this->parent->isOverwrite() || $this->parent->isUpgrade()))
		{
			// Look for an update function or update tag
			$updateElement = $this->manifest->update;

			// Upgrade manually set or update function available or update tag detected
			if ($this->parent->isUpgrade() || ($this->parent->manifestClass && method_exists($this->parent->manifestClass, 'update'))
				|| $updateElement)
			{
				// Force this one
				$this->parent->setOverwrite(true);
				$this->parent->setUpgrade(true);

				if ($this->currentExtensionId)
				{
					// If there is a matching extension mark this as an update
					$this->setRoute('update');
				}
			}
			elseif (!$this->parent->isOverwrite())
			{
				// We didn't have overwrite set, find an update function or find an update tag so lets call it safe
				throw new RuntimeException(
					JText::sprintf(
						'JLIB_INSTALLER_ABORT_MOD_INSTALL_DIRECTORY',
						JText::_('JLIB_INSTALLER_' . $this->route),
						$this->parent->getPath('extension_root')
					)
				);
			}
		}
	}

	/**
	 * Method to copy the extension's base files from the <files> tag(s) and the manifest file
	 *
	 * @return  void
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function copyBaseFiles()
	{
		// TODO: Prepare generic method
	}

	/**
	 * Method to create the extension root path if necessary
	 *
	 * @return  void
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function createExtensionRoot()
	{
		// If the extension directory does not exist, lets create it
		$created = false;

		if (!file_exists($this->parent->getPath('extension_root')))
		{
			if (!$created = JFolder::create($this->parent->getPath('extension_root')))
			{
				throw new RuntimeException(
					JText::sprintf(
						'JLIB_INSTALLER_ABORT_MOD_INSTALL_CREATE_DIRECTORY',
						JText::_('JLIB_INSTALLER_' . $this->route),
						$this->parent->getPath('extension_root')
					)
				);
			}
		}

		/*
		 * Since we created the module directory and will want to remove it if
		 * we have to roll back the installation, let's add it to the
		 * installation step stack
		 */

		if ($created)
		{
			$this->parent->pushStep(array('type' => 'folder', 'path' => $this->parent->getPath('extension_root')));
		}
	}

	/**
	 * Method to handle database transactions for the installer
	 *
	 * @param   string  $route  The action being performed on the database
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function doDatabaseTransactions($route)
	{
		// Get a database connector object
		$db = $this->parent->getDbo();

		// Let's run the install queries for the component
		if (isset($this->manifest->{$route}->sql))
		{
			$result = $this->parent->parseSQLFiles($this->manifest->{$route}->sql);

			if ($result === false)
			{
				// Only rollback if installing
				if ($route == 'install')
				{
					throw new RuntimeException(JText::sprintf('JLIB_INSTALLER_ABORT_INSTALL_SQL_ERROR', JText::_('JLIB_INSTALLER_' . $this->route), $db->stderr(true)));
				}

				return false;
			}
		}

		return true;
	}

	/**
	 * Load language files
	 *
	 * @param   string  $extension  The name of the extension
	 * @param   string  $source     Path to the extension
	 * @param   string  $default    The path to the default language location
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	protected function doLoadLanguage($extension, $source, $default = JPATH_SITE)
	{
		$lang = JFactory::getLanguage();
		$lang->load($extension . '.sys', $source, null, false, false)
			|| $lang->load($extension . '.sys', $default, null, false, false)
			|| $lang->load($extension . '.sys', $source, $lang->getDefault(), false, false)
			|| $lang->load($extension . '.sys', $default, $lang->getDefault(), false, false);
	}

	/**
	 * Actually refresh the extension table cache
	 *
	 * @param   string  $manifestPath  Path to the manifest file
	 *
	 * @return  boolean  Result of operation, true if updated, false on failure
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function doRefreshManifestCache($manifestPath)
	{
		$this->parent->manifest = $this->parent->isManifest($manifestPath);
		$this->parent->setPath('manifest', $manifestPath);

		$manifest_details = JInstaller::parseXMLInstallFile($this->parent->getPath('manifest'));
		$this->parent->extension->manifest_cache = json_encode($manifest_details);
		$this->parent->extension->name = $manifest_details['name'];

		try
		{
			return $this->parent->extension->store();
		}
		catch (RuntimeException $e)
		{
			throw new RuntimeException(JText::_('JLIB_INSTALLER_ERROR_REFRESH_MANIFEST_CACHE'));
		}
	}

	/**
	 * Method to finalise the installation processing
	 *
	 * @return  void
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function finaliseInstall()
	{
		// TODO: Prepare generic method
	}

	/**
	 * Get the filtered extension element from the manifest
	 *
	 * @param   string  $element  Optional element name to be converted
	 *
	 * @return  string  The filtered element
	 *
	 * @since   3.1
	 */
	public function getElement($element = null)
	{
		if (!$element)
		{
			// Ensure the element is a string
			$element = (string) $this->manifest->element;
		}
		if (!$element)
		{
			$element = $this->getName();
		}

		// Filter the name for illegal characters
		$element = JFilterInput::getInstance()->clean($element, 'cmd');

		return $element;
	}

	/**
	 * Find the manifest file and return it as an object.
	 *
	 * @return  object  Manifest object
	 *
	 * @since   3.1
	 */
	public function findManifest()
	{
		// We are trying to find manifest for the installed extension.
		// TODO: handle locally in every adapter (see uninstall to get some hints).
		// TODO: do we also need the path to the file?
		$manifest = $this->parent->getManifest();

		return $manifest;
	}

	/**
	 * Get the manifest object.
	 *
	 * @return  object  Manifest object
	 *
	 * @since   3.1
	 * @deprecated
	 * @todo Check if this function exists in the original code..
	 */
	public function getManifest()
	{
		if (!$this->manifest)
		{
			$this->manifest = $this->findManifest();
		}

		return $this->manifest;
	}

	/**
	 * Get the filtered component name from the manifest
	 *
	 * @return  string  The filtered name
	 *
	 * @since   3.1
	 */
	public function getName()
	{
		// Ensure the name is a string
		$name = (string) $this->manifest->name;

		// Filter the name for illegal characters
		$name = JFilterInput::getInstance()->clean($name, 'string');

		return $name;
	}

	/**
	 * Retrieves the parent object
	 *
	 * @return  object parent
	 *
	 * @since   3.1
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Get the install route being followed
	 *
	 * @return  string  The install route
	 *
	 * @since   3.1
	 */
	public function getRoute()
	{
		return $this->route;
	}

	/**
	 * Get the class name for the install adapter script.
	 *
	 * @return  string  The class name.
	 *
	 * @since   3.1
	 */
	protected function getScriptClassName()
	{
		// Support element names like 'en-GB'
		$className = JFilterInput::getInstance()->clean($this->element, 'cmd') . 'InstallerScript';

		// Cannot have - in class names
		$className = str_replace('-', '', $className);

		return $className;
	}

	/**
	 * Generic install method for extensions
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.1
	 * @throws  Exception|RuntimeException
	 */
	public function install()
	{
		// Get the component description
		$description = (string) $this->manifest->description;
		if ($description)
		{
			$this->parent->message = JText::_($description);
		}
		else
		{
			$this->parent->message = '';
		}

		/*
		 * ---------------------------------------------------------------------------------------------
		 * Extension Precheck and Setup Section
		 * ---------------------------------------------------------------------------------------------
		 */

		// Setup the install paths and perform other prechecks as necessary
		try
		{
			$this->setupInstallPaths();
		}
		catch (RuntimeException $e)
		{
			throw $e;
		}

		// Check to see if an extension by the same name is already installed.
		try
		{
			$this->checkExistingExtension();
		}
		catch (RuntimeException $e)
		{
			throw $e;
		}

		// Check if the extension is present in the filesystem
		try
		{
			$this->checkExtensionInFilesystem();
		}
		catch (RuntimeException $e)
		{
			throw $e;
		}

		/*
		 * ---------------------------------------------------------------------------------------------
		 * Installer Trigger Loading
		 * ---------------------------------------------------------------------------------------------
		 */

		$this->setupScriptfile();
		$this->triggerManifestScript('preflight');

		/*
		 * ---------------------------------------------------------------------------------------------
		 * Filesystem Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */

		// If the extension directory does not exist, lets create it
		try
		{
			$this->createExtensionRoot();
		}
		catch (RuntimeException $e)
		{
			throw $e;
		}

		// Copy all necessary files
		try
		{
			$this->copyBaseFiles();
		}
		catch (RuntimeException $e)
		{
			throw $e;
		}

		// Parse optional tags
		$this->parseOptionalTags();

		/*
		 * ---------------------------------------------------------------------------------------------
		 * Database Processing Section
		 * ---------------------------------------------------------------------------------------------
		 */

		try
		{
			$this->storeExtension();
		}
		catch (RuntimeException $e)
		{
			throw $e;
		}

		try
		{
			$this->parseQueries();
		}
		catch (RuntimeException $e)
		{
			throw $e;
		}

		// Run the custom method based on the route
		$this->triggerManifestScript($this->route);

		/*
		 * ---------------------------------------------------------------------------------------------
		 * Finalization and Cleanup Section
		 * ---------------------------------------------------------------------------------------------
		 */

		try
		{
			$this->finaliseInstall();
		}
		catch (RuntimeException $e)
		{
			throw $e;
		}

		// And now we run the postflight
		$this->triggerManifestScript('postflight');

		return $this->extension->extension_id;
	}

	/**
	 * Method to parse optional tags in the manifest
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	protected function parseOptionalTags()
	{
		// TODO: Prepare generic method
	}

	/**
	 * Method to parse the queries specified in the <sql> tags
	 *
	 * @return  void
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function parseQueries()
	{
		// Let's run the queries for the plugin
		if ($this->route == 'install')
		{
			if (!$this->doDatabaseTransactions('install'))
			{
				// TODO: Exception
				return false;
			}

			// Set the schema version to be the latest update version
			if ($this->manifest->update)
			{
				$this->parent->setSchemaVersion($this->manifest->update->schemas, $this->extension->extension_id);
			}
		}
		elseif ($this->route == 'update')
		{
			if ($this->manifest->update)
			{
				$result = $this->parent->parseSchemaUpdates($this->manifest->update->schemas, $this->extension->extension_id);

				if ($result === false)
				{
					// Install failed, rollback changes
					throw new RuntimeException(JText::sprintf('JLIB_INSTALLER_ABORT_PLG_UPDATE_SQL_ERROR', $this->db->stderr(true)));
				}
			}
		}
	}

	/**
	 * Method to do any prechecks and setup the install paths for the extension
	 *
	 * @return  void
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function setupInstallPaths()
	{
		// TODO: Prepare generic method
	}

	/**
	 * Set the install route being followed
	 *
	 * @param   string  $route  The install route being followed
	 *
	 * @return  JInstallerAdapter  Instance of this class to support chaining
	 *
	 * @since   3.1
	 */
	public function setRoute($route)
	{
		$this->route = $route;

		return $this;
	}

	/**
	 * Setup the manifest script file for those adapters that use it.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	protected function setupScriptfile()
	{
		// If there is an manifest class file, lets load it; we'll copy it later (don't have dest yet)
		$this->scriptElement = (string) $this->manifest->scriptfile;

		if ($this->scriptElement)
		{
			$manifestScriptFile = $this->parent->getPath('source') . '/' . $this->scriptElement;

			if (is_file($manifestScriptFile))
			{
				// Load the file
				include_once $manifestScriptFile;
			}

			$classname = $this->getScriptClassName();

			if (class_exists($classname))
			{
				// Create a new instance
				$this->parent->manifestClass = new $classname($this);

				// And set this so we can copy it later
				$this->manifest_script = $this->scriptElement;
			}
		}
	}

	/**
	 * Method to prepare the uninstall script
	 *
	 * This method populates the $this->extension object, checks whether the extension is protected,
	 * and sets the extension paths
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.1
	 * @todo    Cleanup the JText strings
	 */
	protected function setupUninstall()
	{
		// First order of business will be to load the component object table from the database.
		// This should give us the necessary information to proceed.
		if (!$this->extension->extension_id)
		{
			JLog::add(JText::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_ERRORUNKOWNEXTENSION'), JLog::WARNING, 'jerror');

			return false;
		}

		// Is the component we are trying to uninstall a core one?
		// Because that is not a good idea...
		if ($this->extension->protected)
		{
			JLog::add(JText::_('JLIB_INSTALLER_ERROR_COMP_UNINSTALL_WARNCORECOMPONENT'), JLog::WARNING, 'jerror');

			return false;
		}

		return true;
	}

	/**
	 * Method to store the extension to the database
	 *
	 * @return  void
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function storeExtension()
	{
		// TODO: Prepare generic method
	}

	/**
	 * Executes a custom install script method
	 *
	 * @param   string  $method  The install method to execute
	 *
	 * @return  boolean  True on success
	 *
	 * @since   3.1
	 * @throws  RuntimeException
	 */
	protected function triggerManifestScript($method)
	{
		ob_start();
		ob_implicit_flush(false);

		if ($this->parent->manifestClass && method_exists($this->parent->manifestClass, $method))
		{
			switch ($method)
			{
				// The preflight and postflight take the route as a param
				case 'preflight':
				case 'postflight':
					if ($this->parent->manifestClass->$method($this->route, $this) === false)
					{
						if ($method != 'postflight')
						{
							// The script failed, rollback changes
							throw new RuntimeException(JText::_('JLIB_INSTALLER_ABORT_INSTALL_CUSTOM_INSTALL_FAILURE'));
						}
					}
					break;

				// The install, uninstall, and update methods only pass this object as a param
				case 'install':
				case 'uninstall':
				case 'update':
					if ($this->parent->manifestClass->$method($this) === false)
					{
						if ($method != 'uninstall')
						{
							// The script failed, rollback changes
							throw new RuntimeException(JText::_('JLIB_INSTALLER_ABORT_INSTALL_CUSTOM_INSTALL_FAILURE'));
						}
					}
					break;
			}
		}

		// Append to the message object
		$this->extensionMessage .= ob_get_clean();

		// If in postflight or uninstall, set the message for display
		if (($method == 'uninstall' || $method == 'postflight') && $this->extensionMessage != '')
		{
			$this->parent->extension_message = $this->extensionMessage;
		}

		return true;
	}

	/**
	 * Custom update method
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.1
	 */
	public function update()
	{
		// Set the overwrite setting
		$this->parent->setOverwrite(true);
		$this->parent->setUpgrade(true);

		// Go to install which handles updates properly
		return $this->install();
	}
}
