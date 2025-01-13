<?php
/**
 *  \file       /custom/modFactureCustom.class.php
 *  \ingroup    facture
 *  \brief      Module to customize invoices with QR code and payment link
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modFactureCustom extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, and hooks.
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        // Set module properties
        $this->db = $db;

        // Unique ID for the module
        $this->numero = 123456; // Choose an unused unique number

        // Rights class (used for permissions)
        $this->rights_class = 'facturecustom';

        // Module family and name
        $this->family = 'modules'; // Family of modules
        $this->module_position = 500; // Order of appearance in the list
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = $langs->trans("Module to add QR code and payment link to invoices");

        // Module version
        $this->version = '1.0';

        // Load language files for this module
        $this->langfiles = array('facturecustom@facturecustom');

        // Hooks used by the module
        $this->module_parts = array(
            'hooks' => array('pdfgeneration') // Hook to modify invoice PDFs
        );

        // Configuration page URL
        $this->config_page_url = array("facturecustom_setup.php@facturecustom");

        // No additional constants, boxes, or rights for now
        $this->const = array();
        $this->boxes = array();
        $this->rights = array();
        $this->menu = array();
    }
}
