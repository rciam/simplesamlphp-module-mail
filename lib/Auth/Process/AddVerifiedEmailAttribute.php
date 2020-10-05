<?php
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

class sspmod_mail_Auth_Process_AddVerifiedEmailAttribute extends SimpleSAML_Auth_ProcessingFilter
{

    private $emailAttribute = 'mail';

    private $verifiedEmailAttribute = 'voPersonVerifiedEmail';

    private $idpEntityIdAttribute = 'idpEntityId';

    private $idpEntityIdIncludeList = array();

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
                SimpleSAML_Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'emailAttribute' not a string literal");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'emailAttribute' not a string literal");
            }
            $this->emailAttribute = $config['emailAttribute'];
        }

        if (array_key_exists('verifiedEmailAttribute', $config)) {
            if (!is_string($config['verifiedEmailAttribute'])) {
                SimpleSAML_Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'verifiedEmailAttribute' not a string literal");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'verifiedEmailAttribute' not a string literal");
            }
            $this->verifiedEmailAttribute = $config['verifiedEmailAttribute'];
        }

        if (array_key_exists('idpEntityIdAttribute', $config)) {
            if (!is_string($config['idpEntityIdAttribute'])) {
                SimpleSAML_Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'idpEntityIdAttribute' not a string literal");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'idpEntityIdAttribute' not a string literal");
            }
            $this->idpEntityIdAttribute = $config['idpEntityIdAttribute'];
        }

        if (array_key_exists('idpEntityIdIncludeList', $config)) {
            if (!is_array($config['idpEntityIdIncludeList'])) {
                SimpleSAML_Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'idpEntityIdIncludeList' not an array");
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'idpEntityIdIncludeList' not an array");
            }
            $this->idpEntityIdIncludeList = $config['idpEntityIdIncludeList'];
        }

        if (array_key_exists('replace', $config)) {
            if (!is_bool($config['replace'])) {
                SimpleSAML_Logger::error("[mail:AddVerifiedEmailAttribute] Configuration error: 'replace' not a boolean");
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
            SimpleSAML_Logger::debug("[mail:AddVerifiedEmailAttribute] process: Cannot generate " . $this->verifiedEmailAttribute . " attribute");
            return;
        }

        // Nothing to do if verified email attribute already exists and replace is set to false
        if (!empty($state['Attributes'][$this->verifiedEmailAttribute]) && !$this->replace) {
            SimpleSAML_Logger::debug("[mail:AddVerifiedEmailAttribute] process: Cannot replace existing " . $this->verifiedEmailAttribute . " attribute");
            return;
        }

        SimpleSAML_Logger::debug("[mail:AddVerifiedEmailAttribute] process: input: " . $this->emailAttribute . " = " . var_export($state['Attributes'][$this->emailAttribute], true));
        if (!empty($state['Attributes'][$this->idpEntityIdAttribute]) && in_array($state['Attributes'][$this->idpEntityIdAttribute], $this->idpEntityIdIncludeList)) {
            $state['Attributes'][$this->verifiedEmailAttribute] = $state['Attributes'][$this->emailAttribute];
            SimpleSAML_Logger::debug("[mail:AddVerifiedEmailAttribute] process: output: " . $this->verifiedEmailAttribute . " = " . var_export($state['Attributes'][$this->verifiedEmailAttribute], true));
        }

    }
}

