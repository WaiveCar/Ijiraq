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

def charge_for_ad(user_id, email, card_number, exp_month, exp_year, cvc,  amount, ad_id):
  try:
    customer = stripe.Customer.create(description='Stripe customer for Oliver with id {} and email {}'.format(user_id, email))
    card = stripe.Customer.create_source(
      customer.id,
      source={
        'object': 'card',
        'number': card_number,
        'exp_month': exp_month,
        'exp_year': exp_year,
        'cvc': cvc,
        'currency': 'usd',
      },  
    )
    charge = stripe.Charge.create(
        amount=amount, 
        currency='usd', 
        customer=customer.id, 
        description='Charge for Oliver ad #{}'.format(ad_id),
    )
    return charge
  except Exception as e:
    print('error making stripe request', e)
    raise Exception

def retrieve_cards_for_user(stripe_id):
  try: 
    return stripe.Customer.list_sources(stripe_id)
  except Exception as e:
    print('error making stripe request', e)
    raise Exception

def update_card(stripe_id, card_id, update_obj):
  try: 
    return stripe.Customer.modify_source(
      stripe_id,
      card_id,
      metadata=update_obj,
    )
  except Exception as e:
    print('error making stripe request', e)
    raise Exception


#print(charge_for_ad(1, 'daleighan@gmail.com', '4242424242424242', 1, 2021, 113, 1000, 1))
print(retrieve_cards_for_user('cus_G0OgG30WYANHBs'))
print(update_card('cus_G0OgG30WYANHBs', 'card_1FUNsxHjZj603nmB4Cp2KNST', {'exp_month': 5}))
