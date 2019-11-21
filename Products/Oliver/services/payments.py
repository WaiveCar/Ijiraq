import stripe
import os

config = {
    'secret': 'sk_test_n9MTqk5eeeQeqwN19XVnTjhN',
    'key': 'pk_test_zO3lYXra7dNIcI4JyFBitshk',
  } if 'ENV' not in os.environ or os.environ['ENV'] != 'production' else {
    'secret': 'sk_live_cJmUPQAyZcQG67pnUEH81Bi5',
    'key': 'pk_live_aT8u3UGOje5ryCk1Q0R9rleK',
  }
stripe.api_key = config['secret']

# The function below will be what is used to actually charge users for an ad. 
# I am going to add an outline of additional logic that will need to be here once 
# this code is incorporated into the server
def charge_for_notice(user_id, email, card, amount, ad_id):
  try:
    # First, a user will be checked for a stripe_id. If no stripe_id is present,
    # create_customer will need to be called
    customer = create_customer(user_id, email)
    # Then, we will need to check if a user has a card in stripe already or if they 
    # provided one with this request. If neither, we need to create a card in stripe 
    # as below
    card = create_card(customer.id, card)
    # Lastly, we will need to charge the user for their purchase
    return create_charge(
        customer.id, 
        amount, 
        'Charge for Oliver ad #{} for user with email {}'.format(ad_id, email)
    )
  except Exception as e:
    raise e

def create_customer(user_id, email):
  try: 
    return stripe.Customer.create(description='Stripe customer for Oliver with id {} and email {}'.format(user_id, email))
  except Exception as e:
    raise e

def retrieve_cards_for_user(stripe_id):
  try: 
    return stripe.Customer.list_sources(stripe_id)
  except Exception as e:
    raise e

def create_card(stripe_id, card):
  try: 
    return stripe.Customer.create_source(
      stripe_id,
      source={
        'object': 'card',
        'number': card['card_number'],
        'exp_month': card['exp_month'],
        'exp_year': card['exp_year'],
        'cvc': card['cvc'],
        'currency': 'usd',
      },  
    )
  except Exception as e:
    raise e

def update_card(stripe_id, card_id, update_obj):
  try: 
    return stripe.Customer.modify_source(
      stripe_id,
      card_id,
      metadata=update_obj,
    )
  except Exception as e:
    raise e

def delete_card(stripe_id, card_id):
  try: 
    return stripe.Customer.delete_source(
      stripe_id,
      card_id,
    )
  except Exception as e:
    raise e

def create_charge(customer_id, amount, description):
  try: 
    return stripe.Charge.create(
        amount=amount, 
        currency='usd', 
        customer=customer_id, 
        description=description,
    )
  except Exception as e:
    raise e

def list_charges_by_user(stripe_id):
  try:
    return stripe.Charge.list(customer=stripe_id)
  except Exception as e:
    raise e

def refund_charge(charge_id, amount=None):
  try:
    return stripe.Refund.create(
      charge=charge_id,
      amount=amount,
    )
  except Exception as e:
    raise e
  

