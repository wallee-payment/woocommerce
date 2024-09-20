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
 * This service handles webhooks.
 */
class WC_Wallee_Service_Webhook extends WC_Wallee_Service_Abstract {

	const WALLEE_MANUAL_TASK = 1487165678181;
	const WALLEE_PAYMENT_METHOD_CONFIGURATION = 1472041857405;
	const WALLEE_TRANSACTION = 1472041829003;
	const WALLEE_DELIVERY_INDICATION = 1472041819799;
	const WALLEE_TRANSACTION_INVOICE = 1472041816898;
	const WALLEE_TRANSACTION_COMPLETION = 1472041831364;
	const WALLEE_TRANSACTION_VOID = 1472041867364;
	const WALLEE_REFUND = 1472041839405;
	const WALLEE_TOKEN = 1472041806455;
	const WALLEE_TOKEN_VERSION = 1472041811051;

	/**
	 * The webhook listener API service.
	 *
	 * @var \Wallee\Sdk\Service\WebhookListenerService
	 */
	private $webhook_listener_service;

	/**
	 * The webhook url API service.
	 *
	 * @var \Wallee\Sdk\Service\WebhookUrlService
	 */
	private $webhook_url_service;


	/**
	 * Webhook entities.
	 *
	 * @var array
	 */
	private $webhook_entities = array();

	/**
	 * Construct.
	 *
	 * Constructor to register the webhook entites.
	 */
	public function __construct() {
		$this->init_webhook_entities();
	}

	/**
	 * Initializes webhook entities with their specific configurations.
	 */
	private function init_webhook_entities() {
		$this->webhook_entities[ self::WALLEE_MANUAL_TASK ] = new WC_Wallee_Webhook_Entity(
			self::WALLEE_MANUAL_TASK,
			'Manual Task',
			array(
				\Wallee\Sdk\Model\ManualTaskState::DONE,
				\Wallee\Sdk\Model\ManualTaskState::EXPIRED,
				\Wallee\Sdk\Model\ManualTaskState::OPEN,
			),
			'WC_Wallee_Webhook_Manual_Task'
		);
		$this->webhook_entities[ self::WALLEE_PAYMENT_METHOD_CONFIGURATION ] = new WC_Wallee_Webhook_Entity(
			self::WALLEE_PAYMENT_METHOD_CONFIGURATION,
			'Payment Method Configuration',
			array(
				\Wallee\Sdk\Model\CreationEntityState::ACTIVE,
				\Wallee\Sdk\Model\CreationEntityState::DELETED,
				\Wallee\Sdk\Model\CreationEntityState::DELETING,
				\Wallee\Sdk\Model\CreationEntityState::INACTIVE,
			),
			'WC_Wallee_Webhook_Method_Configuration',
			true
		);
		$this->webhook_entities[ self::WALLEE_TRANSACTION ] = new WC_Wallee_Webhook_Entity(
			self::WALLEE_TRANSACTION,
			'Transaction',
			array(
				\Wallee\Sdk\Model\TransactionState::CONFIRMED,
				\Wallee\Sdk\Model\TransactionState::AUTHORIZED,
				\Wallee\Sdk\Model\TransactionState::DECLINE,
				\Wallee\Sdk\Model\TransactionState::FAILED,
				\Wallee\Sdk\Model\TransactionState::FULFILL,
				\Wallee\Sdk\Model\TransactionState::VOIDED,
				\Wallee\Sdk\Model\TransactionState::COMPLETED,
				\Wallee\Sdk\Model\TransactionState::PROCESSING,
			),
			'WC_Wallee_Webhook_Transaction'
		);
		$this->webhook_entities[ self::WALLEE_DELIVERY_INDICATION ] = new WC_Wallee_Webhook_Entity(
			self::WALLEE_DELIVERY_INDICATION,
			'Delivery Indication',
			array(
				\Wallee\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED,
			),
			'WC_Wallee_Webhook_Delivery_Indication'
		);

		$this->webhook_entities[ self::WALLEE_TRANSACTION_INVOICE ] = new WC_Wallee_Webhook_Entity(
			self::WALLEE_TRANSACTION_INVOICE,
			'Transaction Invoice',
			array(
				\Wallee\Sdk\Model\TransactionInvoiceState::NOT_APPLICABLE,
				\Wallee\Sdk\Model\TransactionInvoiceState::PAID,
				\Wallee\Sdk\Model\TransactionInvoiceState::DERECOGNIZED,
			),
			'WC_Wallee_Webhook_Transaction_Invoice'
		);

		$this->webhook_entities[ self::WALLEE_TRANSACTION_COMPLETION ] = new WC_Wallee_Webhook_Entity(
			self::WALLEE_TRANSACTION_COMPLETION,
			'Transaction Completion',
			array(
				\Wallee\Sdk\Model\TransactionCompletionState::FAILED,
				\Wallee\Sdk\Model\TransactionCompletionState::SUCCESSFUL,
			),
			'WC_Wallee_Webhook_Transaction_Completion'
		);

		$this->webhook_entities[ self::WALLEE_TRANSACTION_VOID ] = new WC_Wallee_Webhook_Entity(
			self::WALLEE_TRANSACTION_VOID,
			'Transaction Void',
			array(
				\Wallee\Sdk\Model\TransactionVoidState::FAILED,
				\Wallee\Sdk\Model\TransactionVoidState::SUCCESSFUL,
			),
			'WC_Wallee_Webhook_Transaction_Void'
		);

		$this->webhook_entities[ self::WALLEE_REFUND ] = new WC_Wallee_Webhook_Entity(
			self::WALLEE_REFUND,
			'Refund',
			array(
				\Wallee\Sdk\Model\RefundState::FAILED,
				\Wallee\Sdk\Model\RefundState::SUCCESSFUL,
			),
			'WC_Wallee_Webhook_Refund'
		);
		$this->webhook_entities[ self::WALLEE_TOKEN ] = new WC_Wallee_Webhook_Entity(
			self::WALLEE_TOKEN,
			'Token',
			array(
				\Wallee\Sdk\Model\CreationEntityState::ACTIVE,
				\Wallee\Sdk\Model\CreationEntityState::DELETED,
				\Wallee\Sdk\Model\CreationEntityState::DELETING,
				\Wallee\Sdk\Model\CreationEntityState::INACTIVE,
			),
			'WC_Wallee_Webhook_Token'
		);
		$this->webhook_entities[ self::WALLEE_TOKEN_VERSION ] = new WC_Wallee_Webhook_Entity(
			self::WALLEE_TOKEN_VERSION,
			'Token Version',
			array(
				\Wallee\Sdk\Model\TokenVersionState::ACTIVE,
				\Wallee\Sdk\Model\TokenVersionState::OBSOLETE,
			),
			'WC_Wallee_Webhook_Token_Version'
		);
	}

	/**
	 * Installs the necessary webhooks in wallee.
	 */
	public function install() {
		$space_id = get_option( WooCommerce_Wallee::WALLEE_CK_SPACE_ID );
		if ( ! empty( $space_id ) ) {
			$webhook_url = $this->get_webhook_url( $space_id );
			if ( null == $webhook_url ) {
				$webhook_url = $this->create_webhook_url( $space_id );
			}
			$existing_listeners = $this->get_webhook_listeners( $space_id, $webhook_url );
			foreach ( $this->webhook_entities as $webhook_entity ) {
				/* @var WC_Wallee_Webhook_Entity $webhook_entity */ //phpcs:ignore
				$exists = false;
				foreach ( $existing_listeners as $existing_listener ) {
					if ( $existing_listener->getEntity() == $webhook_entity->get_id() ) {
						$exists = true;
					}
				}
				if ( ! $exists ) {
					$this->create_webhook_listener( $webhook_entity, $space_id, $webhook_url );
				}
			}
		}
	}

	/**
	 * Get the webhook entity for a specific ID or throws an exception if not found.
	 *
	 * @param mixed $id The ID of the webhook entity to retrieve.
	 * @return WC_Wallee_Webhook_Entity The webhook entity associated with the given ID.
	 * @throws Exception If the webhook entity cannot be found.
	 */
	public function get_webhook_entity_for_id( $id ) {
		if ( ! isset( $this->webhook_entities[ $id ] ) ) {
			throw new Exception( sprintf( 'Could not retrieve webhook model for listener entity id: %s', esc_attr( $id ) ) );
		}
		return $this->webhook_entities[ $id ];
	}

	/**
	 * Create a webhook listener.
	 *
	 * @param WC_Wallee_Webhook_Entity $entity entity.
	 * @param int $space_id space id.
	 * @param \Wallee\Sdk\Model\WebhookUrl $webhook_url webhook url.
	 *
	 * @return \Wallee\Sdk\Model\WebhookListenerCreate
	 * @throws \Exception Exception.
	 */
	protected function create_webhook_listener( WC_Wallee_Webhook_Entity $entity, $space_id, \Wallee\Sdk\Model\WebhookUrl $webhook_url ) {
		$webhook_listener = new \Wallee\Sdk\Model\WebhookListenerCreate();
		$webhook_listener->setEntity( $entity->get_id() );
		$webhook_listener->setEntityStates( $entity->get_states() );
		$webhook_listener->setName( 'Woocommerce ' . $entity->get_name() );
		$webhook_listener->setState( \Wallee\Sdk\Model\CreationEntityState::ACTIVE );
		$webhook_listener->setUrl( $webhook_url->getId() );
		$webhook_listener->setNotifyEveryChange( $entity->is_notify_every_change() );
		$webhook_listener->setEnablePayloadSignatureAndState( true );
		return $this->get_webhook_listener_service()->create( $space_id, $webhook_listener );
	}

	/**
	 * Returns the existing webhook listeners.
	 *
	 * @param int $space_id space id.
	 * @param \Wallee\Sdk\Model\WebhookUrl $webhook_url webhook url.
	 *
	 * @return \Wallee\Sdk\Model\WebhookListener[]
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_listeners( $space_id, \Wallee\Sdk\Model\WebhookUrl $webhook_url ) {
		$query = new \Wallee\Sdk\Model\EntityQuery();
		$filter = new \Wallee\Sdk\Model\EntityQueryFilter();
		$filter->setType( \Wallee\Sdk\Model\EntityQueryFilterType::_AND );
		$filter->setChildren(
			array(
				$this->create_entity_filter( 'state', \Wallee\Sdk\Model\CreationEntityState::ACTIVE ),
				$this->create_entity_filter( 'url.id', $webhook_url->getId() ),
			)
		);
		$query->setFilter( $filter );
		return $this->get_webhook_listener_service()->search( $space_id, $query );
	}

	/**
	 * Creates a webhook url.
	 *
	 * @param int $space_id space id.
	 *
	 * @return \Wallee\Sdk\Model\WebhookUrlCreate
	 * @throws \Exception Exception.
	 */
	protected function create_webhook_url( $space_id ) {
		$webhook_url = new \Wallee\Sdk\Model\WebhookUrlCreate();
		$webhook_url->setUrl( $this->get_url() );
		$webhook_url->setState( \Wallee\Sdk\Model\CreationEntityState::ACTIVE );
		$webhook_url->setName( 'Woocommerce' );
		return $this->get_webhook_url_service()->create( $space_id, $webhook_url );
	}

	/**
	 * Returns the existing webhook url if there is one.
	 *
	 * @param int $space_id space id.
	 *
	 * @return \Wallee\Sdk\Model\WebhookUrl
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_url( $space_id ) {
		$query = new \Wallee\Sdk\Model\EntityQuery();
		$filter = new \Wallee\Sdk\Model\EntityQueryFilter();
		$filter->setType( \Wallee\Sdk\Model\EntityQueryFilterType::_AND );
		$filter->setChildren(
			array(
				$this->create_entity_filter( 'state', \Wallee\Sdk\Model\CreationEntityState::ACTIVE ),
				$this->create_entity_filter( 'url', $this->get_url() ),
			)
		);
		$query->setFilter( $filter );
		$query->setNumberOfEntities( 1 );
		try {
			$result = $this->get_webhook_url_service()->search( $space_id, $query );
			if ( ! empty( $result ) ) {
				return $result[0];
			} else {
				return null;
			}
		} catch ( \Exception $e ) {
			WooCommerce_Wallee::instance()->log( $e->getMessage(), WC_Log_Levels::ERROR );
		}
	}

	/**
	 * Returns the webhook endpoint URL.
	 *
	 * @return string
	 */
	protected function get_url() {
		return add_query_arg( 'wc-api', 'wallee_webhook', home_url( '/' ) );
	}

	/**
	 * Returns the webhook listener API service.
	 *
	 * @return \Wallee\Sdk\Service\WebhookListenerService
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_listener_service() {
		if ( null == $this->webhook_listener_service ) {
			$this->webhook_listener_service = new \Wallee\Sdk\Service\WebhookListenerService( WC_Wallee_Helper::instance()->get_api_client() );
		}
		return $this->webhook_listener_service;
	}

	/**
	 * Returns the webhook url API service.
	 *
	 * @return \Wallee\Sdk\Service\WebhookUrlService
	 * @throws \Exception Exception.
	 */
	protected function get_webhook_url_service() {
		if ( null == $this->webhook_url_service ) {
			$this->webhook_url_service = new \Wallee\Sdk\Service\WebhookUrlService( WC_Wallee_Helper::instance()->get_api_client() );
		}
		return $this->webhook_url_service;
	}
}
