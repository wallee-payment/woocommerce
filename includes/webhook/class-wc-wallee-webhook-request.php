<?php
/**
 * Plugin Name: Wallee
 * Author: wallee AG
 * Text Domain: wallee
 * Domain Path: /languages/
 *
 * Wallee
 * This plugin will add support for all Wallee payments methods and connect the Wallee servers to your WooCommerce webshop (https://www.wallee.com).
 *
 * @category Class
 * @package  Wallee
 * @author   wallee AG (https://www.wallee.com)
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

defined( 'ABSPATH' ) || exit;

/**
 * Webhook request.
 */
class WC_Wallee_Webhook_Request {
	/**
	 * Event id.
	 *
	 * @var mixed
	 */
	private $event_id;

	/**
	 * Entity id.
	 *
	 * @var mixed
	 */
	private $entity_id;

	/**
	 * Listener entity id.
	 *
	 * @var mixed
	 */
	private $listener_entity_id;

	/**
	 * Listener entity technical name.
	 *
	 * @var mixed
	 */
	private $listener_entity_technical_name;

	/**
	 * Space id.
	 *
	 * @var mixed
	 */
	private $space_id;

	/**
	 * Webhook listener id.
	 *
	 * @var mixed
	 */
	private $webhook_listener_id;

	/**
	 * Timestamp.
	 *
	 * @var mixed
	 */
	private $timestamp;

	/**
	 * Entity state.
	 *
	 * @var mixed
	 */
	private $state;

	/**
	 * Constructor.
	 *
	 * @param stdClass $model model.
	 */
	public function __construct( $model ) {
		// phpcs:ignore
		$this->event_id = $model->eventId;
		// phpcs:ignore
		$this->entity_id = $model->entityId;
		// phpcs:ignore
		$this->listener_entity_id = $model->listenerEntityId;
		// phpcs:ignore
		$this->listener_entity_technical_name = $model->listenerEntityTechnicalName;
		// phpcs:ignore
		$this->space_id = $model->spaceId;
		// phpcs:ignore
		$this->webhook_listener_id = $model->webhookListenerId;
		$this->timestamp = $model->timestamp;
		$this->state = $model->state;
	}

	/**
	 * Returns the webhook event's id.
	 *
	 * @return int
	 */
	public function get_event_id() {
		return $this->event_id;
	}

	/**
	 * Returns the id of the webhook event's entity.
	 *
	 * @return int
	 */
	public function get_entity_id() {
		return $this->entity_id;
	}

	/**
	 * Returns the id of the webhook's listener entity.
	 *
	 * @return int
	 */
	public function get_listener_entity_id() {
		return $this->listener_entity_id;
	}

	/**
	 * Returns the technical name of the webhook's listener entity.
	 *
	 * @return string
	 */
	public function get_listener_entity_technical_name() {
		return $this->listener_entity_technical_name;
	}

	/**
	 * Returns the space id.
	 *
	 * @return int
	 */
	public function get_space_id() {
		return $this->space_id;
	}

	/**
	 * Returns the id of the webhook listener.
	 *
	 * @return int
	 */
	public function get_webhook_listener_id() {
		return $this->webhook_listener_id;
	}

	/**
	 * Returns the webhook's timestamp.
	 *
	 * @return string
	 */
	public function get_timestamp() {
		return $this->timestamp;
	}

	/**
	 * Returns the state of the webhook event's entity.
	 *
	 * @return string
	 */
	public function get_state() {
		return $this->state;
	}
}
