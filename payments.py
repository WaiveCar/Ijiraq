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

def charge_for_ad(user_id, email, card, amount, ad_id):
  try:
    customer = create_customer(user_id, email)
    card = create_card(customer.id, card)
    return create_charge(customer.id, amount, ad_id)
  except Exception as e:
    print('Error charging user', e)
    raise Exception

def create_charge(customer_id, amount, ad_id):
  try: 
    return stripe.Charge.create(
        amount=amount, 
        currency='usd', 
        customer=customer_id, 
        description='Charge for Oliver ad #{}'.format(ad_id),
    )
  except Exception as e:
    print('Error creating charge', e)
    raise Exception

def create_customer(user_id, email):
  try: 
    return stripe.Customer.create(description='Stripe customer for Oliver with id {} and email {}'.format(user_id, email))
  except Exception as e:
    print('Error creating user', e)
    raise Exception

def retrieve_cards_for_user(stripe_id):
  try: 
    return stripe.Customer.list_sources(stripe_id)
  except Exception as e:
    print('Error retrieving cards', e)
    raise Exception

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
    print('Error adding new card', e)
    raise Exception

def update_card(stripe_id, card_id, update_obj):
  try: 
    return stripe.Customer.modify_source(
      stripe_id,
      card_id,
      metadata=update_obj,
    )
  except Exception as e:
    print('Error upadating card', e)
    raise Exception

def delete_card(stripe_id, card_id):
  try: 
    return stripe.Customer.delete_source(
      stripe_id,
      card_id,
    )
  except Exception as e:
    print('Error deleting card', e)
    raise Exception



#print(charge_for_ad(1, 'daleighan@gmail.com', {'card_number': '4242424242424242', 'exp_month': 1, 'exp_year': 2021, 'cvc': 111}, 1000, 1))
#print(retrieve_cards_for_user('cus_G0OgG30WYANHBs'))
#print(update_card('cus_G0OgG30WYANHBs', 'card_1FUNsxHjZj603nmB4Cp2KNST', {'exp_month': 5}))
