<?php

namespace Mathielen\CXml\Processor;

use Mathielen\CXml\Credential\CredentialAuthenticatorInterface;
use Mathielen\CXml\Exception\CXmlAuthenticationInvalid;
use Mathielen\CXml\Exception\CXmlCredentialInvalid;
use Mathielen\CXml\Model\Credential;
use Mathielen\CXml\Model\Header;
use Mathielen\CXml\Model\ResponseInterface;
use Mathielen\CXml\Credential\CredentialRepositoryInterface;

class HeaderProcessor
{
    private CredentialRepositoryInterface $credentialRepository;
	private CredentialAuthenticatorInterface $credentialAuthenticator;

	public function __construct(CredentialRepositoryInterface $credentialRepository, CredentialAuthenticatorInterface $credentialAuthenticator)
    {
        $this->credentialRepository = $credentialRepository;
		$this->credentialAuthenticator = $credentialAuthenticator;
	}

    /**
     * @throws CXmlCredentialInvalid
     */
    public function process(Header $header): void
    {
        $this->validatePartys($header);

        $this->authenticateSender($header->getSender()->getCredential());
    }

    /**
     * @throws CXmlCredentialInvalid
     */
    private function validatePartys(Header $header): void
    {
        $this->checkCredentialIsValid($header->getFrom()->getCredential());

        $this->checkCredentialIsValid($header->getTo()->getCredential());

        $this->checkCredentialIsValid($header->getSender()->getCredential());
    }

    /**
     * @throws CXmlCredentialInvalid
     */
    private function checkCredentialIsValid(Credential $testCredential): void
    {
        $existingCredential = $this->credentialRepository->getCredentialByDomainAndId(
            $testCredential->getDomain(),
            $testCredential->getIdentity()
        );

        if (!$existingCredential) {
            throw new CXmlCredentialInvalid("Could not find credentials", $testCredential);
        }
    }

	/**
	 * @throws CXmlAuthenticationInvalid
	 * @throws CXmlCredentialInvalid
	 */
    private function authenticateSender(Credential $testCredential): void
    {
        $actualCredential = $this->credentialRepository->getCredentialByDomainAndId(
            $testCredential->getDomain(),
            $testCredential->getIdentity()
        );

		if (!$actualCredential) {
			throw new CXmlCredentialInvalid("Could not find credentials", $testCredential);
		}

        $this->credentialAuthenticator->authenticate($testCredential);
    }
}
