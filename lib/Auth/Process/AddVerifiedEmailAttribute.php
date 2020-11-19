<?php

namespace SimpleSAML\Module\mail\Auth\Process;

/**
 * Authentication processing filter for generating attribute containing
 * verified email addresses.
 * 
 * Example configuration:
 *
 *    authproc = array(
 *       ...
 *       '61' => array(
 *            'class' => 'mail:AddVerifiedEmailAttribute',
 *            'emailAttribute' => 'email', // Optional, defaults to 'mail'
 *            'verifiedEmailAttribute' => 'verifiedEmail', // Optional, defaults to 'voPersonVerifiedEmail'
 *            'replace' => true,   // Optional, defaults to false 
 *       ),
 *
 * @author Nicolas Liampotis <nliam@grnet.gr>
 * @package SimpleSAMLphp
 */

class AddVerifiedEmailAttribute extends \SimpleSAML\Auth\ProcessingFilter
{
    /**
     * Attribute containing the user's email address(es)
     * @var string
     */
    private $emailAttribute = 'mail';

   /**
     * Attribute containing the user's verified email address(es)
     * @var string
     */
    private $verifiedEmailAttribute = 'voPersonVerifiedEmail';

    /**
     * List of Identity Provider entityIDs for selectively generating the
     * user's verified email address(es). Only email address(es) from
     * identity providers that exist in the include list will be processed.
     * @var array
     */
    private $idpEntityIdIncludeList = array();

    /**
     * Should the existing verified email attribute (if any) be replaced?
     * @var bool
     */
    private $replace = false;

    /**
     * Initialize this filter, parse configuration
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     *
     * @throws Exception If the configuration of the filter is wrong.
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        assert('is_array($config)');


        if (array_key_exists('emailAttribute', $config)) {
            if (!is_string($config['emailAttribute'])) {
                SimpleSAML\Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'emailAttribute' not a string literal");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'emailAttribute' not a string literal");
            }
            $this->emailAttribute = $config['emailAttribute'];
        }

        if (array_key_exists('verifiedEmailAttribute', $config)) {
            if (!is_string($config['verifiedEmailAttribute'])) {
                SimpleSAML\Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'verifiedEmailAttribute' not a string literal");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'verifiedEmailAttribute' not a string literal");
            }
            $this->verifiedEmailAttribute = $config['verifiedEmailAttribute'];
        }

        if (array_key_exists('idpEntityIdIncludeList', $config)) {
            if (!is_array($config['idpEntityIdIncludeList'])) {
                SimpleSAML\Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'idpEntityIdIncludeList' not an array");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'idpEntityIdIncludeList' not an array");
            }
            $this->idpEntityIdIncludeList = $config['idpEntityIdIncludeList'];
        }

        if (array_key_exists('replace', $config)) {
            if (!is_bool($config['replace'])) {
                SimpleSAML\Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'replace' not a boolean");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'replace' not a boolean value");
            }
            $this->replace = $config['replace'];
        }

    }

    /**
     * Apply filter to rename attributes.
     *
     * @param array &$state The current state.
     */
    public function process(&$state)
    {
        assert(is_array($state));
        assert(array_key_exists('Attributes', $state));

        // Nothing to do if email attribute is missing
        if (empty($state['Attributes'][$this->emailAttribute])) {
            SimpleSAML\Logger::debug("[mail:AddVerifiedEmailAttribute] process: Cannot generate " . $this->verifiedEmailAttribute . " attribute: " . $this->emailAttribute . " attribute is missing");
            return;
        }
        SimpleSAML\Logger::debug("[mail:AddVerifiedEmailAttribute] process: input: " . $this->emailAttribute . " = " . var_export($state['Attributes'][$this->emailAttribute], true));

        // Nothing to do if verified email attribute already exists and replace is set to false
        if (!empty($state['Attributes'][$this->verifiedEmailAttribute]) && !$this->replace) {
            SimpleSAML\Logger::debug("[mail:AddVerifiedEmailAttribute] process: Cannot replace existing " . $this->verifiedEmailAttribute . " attribute: replace is set to false");
            return;
        }

        // Retrieve idpEntityId
        $idpEntityId = $this->getIdpEntityId($state);
        // Check if idpEntityId is empty.
        // This should never happen - but if it does log an error message
        if (empty($idpEntityId)) {
            SimpleSAML\Logger::error("[mail:AddVerifiedEmailAttribute] process: Failed to retrieve idpEntityId");
            return;
        }
        SimpleSAML\Logger::debug("[mail:AddVerifiedEmailAttribute] process: input: idpEntityId = " . var_export($idpEntityId, true));

        // Nothing to do if idpEntityId not in include list
        if (!in_array($idpEntityId, $this->idpEntityIdIncludeList)) {
            SimpleSAML\Logger::debug("[mail:AddVerifiedEmailAttribute] process: Will not generate " . $this->verifiedEmailAttribute . " attribute for IdP " . $idpEntityId);
            return;
        }

        // Add verifiedEmailAttribute to state attributes
        $state['Attributes'][$this->verifiedEmailAttribute] = $state['Attributes'][$this->emailAttribute];
        SimpleSAML\Logger::info("[mail:AddVerifiedEmailAttribute] process: Added " . $this->verifiedEmailAttribute . " attribute");
        SimpleSAML\Logger::debug("[mail:AddVerifiedEmailAttribute] process: output: " . $this->verifiedEmailAttribute . " = " . var_export($state['Attributes'][$this->verifiedEmailAttribute], true));

        return;
    }

    private function getIdpEntityId($state)
    {
	if (!empty($state['saml:sp:IdP'])) {
            return $state['saml:sp:IdP'];
        } else if (!empty($state['Source']['entityid'])) {
            return $state['Source']['entityid'];
        }

        return null;
    }

}

