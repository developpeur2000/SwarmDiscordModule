<?php
/**
 * Perforce Swarm Discord Module
 *
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
*/

namespace Discord\Listener;

use Events\Listener\AbstractEventListener;
use Reviews\Model\Review;
use Comments\Model\Comment;
use Zend\EventManager\Event;
use Zend\Http\Client;
use Zend\Http\Request;

class DiscordActivityListener extends AbstractEventListener
{

    public function handleEvent(Event $event)
    {
		//check if activity is worth posting
        $activity = $event->getParam('activity');
		if (!$activity)
		{
			return;
		}
        $logger = $this->services->get('logger');
        $config = $this->services->get('config');
        $p4Admin = $this->services->get('p4_admin');
        // Host address of Swarm for link back URLs
        $host = $this->services->get('ViewHelperManager')->get('qualifiedUrl');

        $logger->info("Discord: event / id => " . $event->getName() . " / " . $event->getParam('id'));
		
		// taken from mail module, this doesn't seem to work
        $data  = (array) $event->getParam('data') + array('quiet' => null);
        $quiet = $event->getParam('quiet', $data['quiet']);
        if ($quiet === true) {
            $logger->info("Discord: event is silent(notifications are being batched), returning.");
            return;
        }
		
		// it's better not to tag user involved in activity, only review author
		//$user = $this->tagUser($config, $activity->get('user'));
		$user = $activity->get('user');
		$action = $activity->get('action');
		$target = $activity->get('target');
		$link = $activity->get('link');
		if (count($link) > 0)
		{
			if (count($link) > 1)
			{
				$link = $host($link[0], $link[1]);
			}
			else
			{
				$link = $host($link[0]);
			}
		}
		$eventString = $user . " " . $action . " " . $target . " => " . $link;
		$reviewId = -1;
		switch($event->getName())
		{
			case "task.comment.batch":
				if (preg_match('/^reviews\/(\d+)$/', $event->getParam('id'), $matches))
				{
					$reviewId = $matches[1];
					$eventString = "Comments have been made on review " . $reviewId . " => " . $host('review', array('review' => $reviewId));
				}
				break;
			case "task.review":
				$reviewId = $event->getParam('id');
				$eventData = $event->getParam('data');
				if (isset($eventData['isAdd']) && $eventData['isAdd'])
				{
					//new review
					$reviewId = 0;
				}
				$logger->info("Discord: event data => " . $eventParams);
				break;
			case "task.comment":
				try {
					$comment = Comment::fetch($event->getParam('id'), $p4Admin);
					$topic = $comment->get('topic');
					if (preg_match('/^reviews\/(\d+)$/', $topic, $matches))
					{
						$reviewId = $matches[1];
					}
				} catch (\Exception $e) {
					$logger->err("Discord: error when fetching comment : " . $e->getMessage());
				}
				//don't treat single comments, waith for the comment.batch
				return;
				break;
			default:
				$logger->info("Discord: event not treated " . $eventString);
				return;
		}
		
		$notify = "";
		if ($reviewId > 0)
		{
			try {
				$review = Review::fetch($reviewId, $p4Admin);
				$reviewAuthor = $review->get('author');
				$notify = $this->tagUser($config, $reviewAuthor) . " ";
			} catch (\Exception $e) {
				$logger->err("Discord: error when fetching review : " . $e->getMessage());
			}
		}
		else if ($reviewId == 0)
		{
			//notify everybody
			$notify = '@everyone ';
		}
		
		$eventString = "Hey " . $notify . "! " . $eventString;
		
		//replace localhost in urls
		$eventString = str_replace('localhost', $config['discord']['swarm_host'], $eventString);
		
        $logger->info("Discord: " . $eventString);

		// URL to POST messages to Discord
		$discordUrl = $config['discord']['discord_webhook'];
        $this->postDiscord($discordUrl, $eventString);
		
        $logger->info("Discord: handleEvent end.");
    }
	
    private function tagUser($config, $username)
    {
		if (isset($config['discord']['discord_ids'][$username]))
		{
			return '<@!' . $config['discord']['discord_ids'][$username] . '>';
		}
		else
		{
			return $username;
		}
	}

    private function postDiscord($url, $msg)
    {
        $logger = $this->services->get('logger');
        $config = $this->services->get('config');

        try {
            $json = array(
                'content' => $msg
            );

            $request = new Request();
            $request->setMethod('POST');
            $request->setUri($url);
            $request->getPost()->fromArray($json);

            $client = new Client();
            $client->setEncType(Client::ENC_FORMDATA);

            // set the http client options; including any special overrides for our host
            $options = $config + array('http_client_options' => array());
            $options = (array) $options['http_client_options'];
            if (isset($options['hosts'][$client->getUri()->getHost()])) {
                $options = (array) $options['hosts'][$client->getUri()->getHost()] + $options;
            }
            unset($options['hosts']);
            $client->setOptions($options);

            // POST request
            $response = $client->dispatch($request);

            if (!$response->isSuccess()) {
                $logger->err(
                    'Discord failed to POST resource: ' . $url . ' (' .
                    $response->getStatusCode() . " - " . $response->getReasonPhrase() . ').',
                    array(
                        'request'   => $client->getLastRawRequest(),
                        'response'  => $client->getLastRawResponse()
                    )
                );
                return false;
            }
            return true;

        } catch (\Exception $e) {
            $logger->err($e);
        }
    }
}