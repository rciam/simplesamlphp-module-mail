<?php

namespace SimpleSAML\Module\mail\Auth\Process;

use SimpleSAML\Auth\ProcessingFilter;
use SimpleSAML\Error\Exception;
use SimpleSAML\Logger;
use SimpleSAML\Utils;

/**
 * Authentication processing filter for generating attribute containing
 * verified email addresses.
 *
 * Example configuration:
 *
 *    authproc = [
 *       ...
 *       '61' => [
 *            'class' => 'mail:AddVerifiedEmailAttribute',
 *            'emailAttribute' => 'email', // Optional, defaults to 'mail'
 *            'verifiedEmailAttribute' => 'verifiedEmail', // Optional, defaults to 'voPersonVerifiedEmail'
 *            'replace' => true,   // Optional, defaults to false
 *            'scopeChecking' => true, // Optional, defaults to false
 *            'homeOrganizationAttribute => 'urn:oid:1.3.6.1.4.1.25178.1.2.9', // Optional, defaults to 'schacHomeOrganization'
 *       ],
 *
 * @author Nicolas Liampotis <nliam@grnet.gr>
 * @author Nick Mastoris <nmastoris@grnet.gr>
 * @package SimpleSAMLphp
 */

class AddVerifiedEmailAttribute extends ProcessingFilter
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
    private $idpEntityIdIncludeList = [];

    /**
     * Should the existing verified email attribute (if any) be replaced?
     * @var bool
     */
    private $replace = false;

    /**
     * Flag that indicates if is needed to check scopes from IdP metadata
     * for marking mail as verified
     * @var bool
     */
    private $scopeChecking = false;

    /**
     * Home organization attribute 
     * @var string
     */
    private $homeOrganizationAttribute = 'schacHomeOrganization';

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
                Logger::error(
                    "[mail:AddVerifiedEmailAttribute] Configuration error: 'emailAttribute' not a string literal"
                );
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'emailAttribute' not a string literal"
                );
            }
            $this->emailAttribute = $config['emailAttribute'];
        }

        if (array_key_exists('verifiedEmailAttribute', $config)) {
            if (!is_string($config['verifiedEmailAttribute'])) {
                Logger::error(
                    "[mail:AddVerifiedEmailAttribute] Configuration error: "
                    . "'verifiedEmailAttribute' not a string literal"
                );
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'verifiedEmailAttribute' not a string literal"
                );
            }
            $this->verifiedEmailAttribute = $config['verifiedEmailAttribute'];
        }

        if (array_key_exists('idpEntityIdIncludeList', $config)) {
            if (!is_array($config['idpEntityIdIncludeList'])) {
                Logger::error(
                    "[mail:AddVerifiedEmailAttribute] Configuration error: 'idpEntityIdIncludeList' not an array"
                );
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'idpEntityIdIncludeList' not an array"
                );
            }
            $this->idpEntityIdIncludeList = $config['idpEntityIdIncludeList'];
        }

        if (array_key_exists('replace', $config)) {
            if (!is_bool($config['replace'])) {
                Logger::error(
                    "[mail:AddVerifiedEmailAttribute] Configuration error: 'replace' not a boolean"
                );
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'replace' not a boolean value"
                );
            }
            $this->replace = $config['replace'];
        }

        if (array_key_exists('scopeChecking', $config)) {
            if (!is_bool($config['scopeChecking'])) {
                Logger::error(
                    "[mail:AddVerifiedEmailAttribute] Configuration error: 'scopeChecking' not a boolean"
                );
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'scopeChecking' not a boolean value"
                );
            }
            $this->scopeChecking = $config['scopeChecking'];
        }

        if (array_key_exists('homeOrganizationAttribute', $config)) {
            if (!is_string($config['homeOrganizationAttribute'])) {
                Logger::error(
                    "[mail:AddVerifiedEmailAttribute] Configuration error: 'homeOrganizationAttribute' not a string"
                );
                throw new Exception(
                    "AddVerifiedEmailAttribute configuration error: 'homeOrganizationAttribute' not a string value"
                );
            }
            $this->homeOrganizationAttribute = $config['homeOrganizationAttribute'];
        }
        
    }

    /**
     * Apply filter to rename attributes.
     *
     * @param array &$state The current state.
     */
    public function process(&$state)
    {
        Logger::debug("Processing the AddVerifiedEmailAttribute filter.");
        assert(is_array($state));
        assert(array_key_exists('Attributes', $state));

        // Nothing to do if email attribute is missing
        if (empty($state['Attributes'][$this->emailAttribute])) {
            Logger::debug(
                "[mail:AddVerifiedEmailAttribute] process: Cannot generate " . $this->verifiedEmailAttribute
                . " attribute: " . $this->emailAttribute . " attribute is missing"
            );
            return;
        }
        Logger::debug(
            "[mail:AddVerifiedEmailAttribute] process: input: " . $this->emailAttribute
            . " = " . var_export($state['Attributes'][$this->emailAttribute], true)
        );

        // Nothing to do if verified email attribute already exists and replace is set to false
        if (!empty($state['Attributes'][$this->verifiedEmailAttribute]) && !$this->replace) {
            Logger::debug(
                "[mail:AddVerifiedEmailAttribute] process: Cannot replace existing "
                . $this->verifiedEmailAttribute . " attribute: replace is set to false"
            );
            return;
        }

        // Retrieve idpEntityId
        $idpEntityId = $this->getIdpEntityId($state);
        // Check if idpEntityId is empty.
        // This should never happen - but if it does log an error message
        if (empty($idpEntityId)) {
            Logger::error("[mail:AddVerifiedEmailAttribute] process: Failed to retrieve idpEntityId");
            return;
        }
        Logger::debug(
            "[mail:AddVerifiedEmailAttribute] process: input: idpEntityId = " . var_export($idpEntityId, true)
        );

        // If idpEntityId not in include list then check scopes, host url, home organization
        if (!in_array($idpEntityId, $this->idpEntityIdIncludeList)) {
            if (!$this->scopeChecking) {
                Logger::debug(
                    "[mail:AddVerifiedEmailAttribute] process: Will not generate "
                    . $this->verifiedEmailAttribute . " attribute for IdP " . $idpEntityId
                );
                return;
            }
            $verified_emails = [];
            // Foreach email check if will be added to verified_emails 
            foreach($state['Attributes'][$this->emailAttribute] as $mail) {
                $this->checkAll($mail, $verified_emails, $state);    
            }
            if(!empty($verified_emails)) {
                // Add verified emails to verifiedEmailAttribute
                $state['Attributes'][$this->verifiedEmailAttribute] = $verified_emails;
            } else {
                Logger::debug(
                    "[mail:AddVerifiedEmailAttribute] process: Will not generate "
                    . $this->verifiedEmailAttribute . " attribute for IdP " . $idpEntityId
                ); 
                return;
            }

        } else {
            // Add verifiedEmailAttribute to state attributes
            $state['Attributes'][$this->verifiedEmailAttribute] = $state['Attributes'][$this->emailAttribute];
        }       
       
        Logger::info(
            "[mail:AddVerifiedEmailAttribute] process: Added " 
            . $this->verifiedEmailAttribute . " attribute"
            . "for IdP " . $idpEntityId
        );
        
        Logger::debug(
            "[mail:AddVerifiedEmailAttribute] process: output: " . $this->verifiedEmailAttribute
            . " = " . var_export($state['Attributes'][$this->verifiedEmailAttribute], true)
        );

        return;
    }

    private function getIdpEntityId($state)
    {
        if (!empty($state['saml:sp:IdP'])) {
            return $state['saml:sp:IdP'];
        } elseif (!empty($state['Source']['entityid'])) {
            return $state['Source']['entityid'];
        }

        return null;
    }

    private function checkIfExists($value, $validScopes) {
        foreach($validScopes as $scope) {
            if(strpos($value, $scope) === strlen($value) - strlen($scope)) {
                return true;
            }
        }
        return false;
    }

    private function checkAll($mail, &$verified_emails, $state) {
        $src = $state['Source'];
        if (array_key_exists('scope', $src) && is_array($src['scope']) && !empty($src['scope'])) {
            $validScopes = $src['scope'];
        }
        if (!empty($state['Source']['SingleSignOnService'])) {
            $ep = Utils\Config\Metadata::getDefaultEndpoint($state['Source']['SingleSignOnService']);
            $host = parse_url($ep['Location'], PHP_URL_HOST) ?? '';
        }
        $emailDomain = explode('@', $mail)[1];
        // Check if email domain is (sub) domain of valid scopes
        // or is (sub) domain of Idp endpoint
        if ((!empty($validScopes) && $this->checkIfExists($emailDomain, $validScopes))
            || (!empty($host) && strpos($emailDomain, $host) === strlen($emailDomain) - strlen($host))
            ) {
                $verified_emails[] = $mail;
                return true;
        }
        // Check if email domain is (sub) domain of home organization attribute
        if (!empty($state['Attributes'][$this->homeOrganizationAttribute]) && !empty($validScopes)) {
            if (count($state['Attributes'][$this->homeOrganizationAttribute])!=1) {
                Logger::warning($this->homeOrganizationAttribute . ' must be single valued.');
            } else if (strpos($emailDomain, $state['Attributes'][$this->homeOrganizationAttribute][0]) === strlen($emailDomain) - strlen($state['Attributes'][$this->homeOrganizationAttribute][0])) {
                $verified_emails[] = $mail;
                return true;
            }
        }
        return false;
    }
}
