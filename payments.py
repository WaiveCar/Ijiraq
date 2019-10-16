import stripe
import os

config = {
    "secret": 'sk_test_n9MTqk5eeeQeqwN19XVnTjhN',
    "pub": 'pk_test_zO3lYXra7dNIcI4JyFBitshk',
  } if os.environ['ENV'] == 'development' else {
    "secret": 'sk_live_cJmUPQAyZcQG67pnUEH81Bi5',
    "pub": 'pk_live_aT8u3UGOje5ryCk1Q0R9rleK',
  }

print(config);
