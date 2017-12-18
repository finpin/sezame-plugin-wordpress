<?php

namespace SezameLib\Request;

class LinkDelete extends Generic
{
	public function setUsername($username)
	{
		$this->_params['username'] = (string) $username;
		return $this;
	}

	public function send()
	{
		/** @var \Buzz\Message\Response $response */
		$response = $this->_client->delete('client/link', $this->_params);
		if (!$response->isOk() && !$response->isEmpty())
			throw new \SezameLib\Exception\Response($response->getReasonPhrase(), $response->getStatusCode());

		return true;
	}
}
