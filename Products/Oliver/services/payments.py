import stripe
import os, logging

config = {
    'secret': 'sk_test_n9MTqk5eeeQeqwN19XVnTjhN',
    'key': 'pk_test_zO3lYXra7dNIcI4JyFBitshk',
    'env': 'test'
  } if 'ENV' not in os.environ or os.environ['ENV'] != 'production' else {
    'secret': 'sk_live_cJmUPQAyZcQG67pnUEH81Bi5',
    'key': 'pk_live_aT8u3UGOje5ryCk1Q0R9rleK',
    'env': 'prod'
  }

stripe.api_key = config['secret']

# The function below will be what is used to actually charge users for an ad. 
# I am going to add an outline of additional logic that will need to be here once 
# this code is incorporated into the server
def charge_for_notice(email, card, amount, ad_id):
  try:
    # First, a user will be checked for a stripe_id. If no stripe_id is present,
    # create_customer will need to be called
    customer = create_customer(email)
    # Then, we will need to check if a user has a card in stripe already or if they 
    # provided one with this request. If neither, we need to create a card in stripe 
    # as below
    card = create_card(customer.id, card)
    # Lastly, we will need to charge the user for their purchase
    return {
      'user': customer, 
      'card': card, 
      'charge': stripe.Charge.create(
        amount=amount, 
        currency='usd', 
        customer=customer.id, 
        description='Charge for Oliver ad #{} for user with email {}'.format(ad_id, email)
      )
    }
  except Exception as e:
    raise e

def create_customer(email):
  try: 
    return stripe.Customer.create(description='Stripe customer for Oliver with email {}'.format(email))
  except stripe.error.CardError as e:
    raise e

def retrieve_cards_for_user(stripe_id):
  try: 
    return stripe.Customer.list_sources(stripe_id)
  except stripe.error.CardError as e:
    raise e

def create_card(stripe_id, card):
  try: 
    logging.warning(card)
    return stripe.Customer.create_source(
      stripe_id,
      source={
        'object': 'card',
        'number': card['number'],
        'exp_month': card['exp_month'],
        'exp_year': card['exp_year'],
        'cvc': card['cvv'],
        'currency': 'usd',
      },  
    )
  except stripe.error.CardError as e:
    raise e

def update_card(stripe_id, card_id, update_obj):
  try: 
    return stripe.Customer.modify_source(
      stripe_id,
      card_id,
      metadata=update_obj,
    )
  except stripe.error.CardError as e:
    raise e

def delete_card(stripe_id, card_id):
  try: 
    return stripe.Customer.delete_source(
      stripe_id,
      card_id,
    )
  except stripe.error.CardError as e:
    raise e

def list_charges_by_user(stripe_id):
  try:
    return stripe.Charge.list(customer=stripe_id)
  except stripe.error.CardError as e:
    raise e

def refund_charge(charge_id, amount=None):
  try:
    return stripe.Refund.create(
      charge=charge_id,
      amount=amount,
    )
  except stripe.error.CardError as e:
    raise e
