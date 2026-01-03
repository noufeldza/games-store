/* Utilitaires cookie, gestion bannière et gestion add-to-cart/wishlist */
(function(){
	// Helpers
	function setCookie(name, value, days) {
		days = days || 30;
		var expires = "";
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days*24*60*60*1000));
			expires = "; expires=" + date.toUTCString();
		}
		// SameSite=Lax pour compatibilité, Secure si en HTTPS
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/" + "; SameSite=Lax" + secure;
	}
	function getCookie(name) {
		var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
		return match ? decodeURIComponent(match[2]) : null;
	}
	function eraseCookie(name) {
		setCookie(name, "", -1);
	}

	// Consent banner
	function showConsentIfNeeded() {
		var consent = getCookie('cookie_consent');
		var el = document.getElementById('cookie-consent');
		if (!consent && el) {
			el.style.display = 'block';
		}
	}
	function acceptCookies() {
		setCookie('cookie_consent', 'accepted', 365);
		var el = document.getElementById('cookie-consent');
		if (el) el.style.display = 'none';
	}
	function rejectCookies() {
		setCookie('cookie_consent', 'rejected', 365);
		// supprimer cookies non essentiels
		eraseCookie('cart');
		eraseCookie('wishlist');
		var el = document.getElementById('cookie-consent');
		if (el) el.style.display = 'none';
	}

	// Cart logic (stored as JSON: { gameId: qty, ... })
	function getCart() {
		try {
			var raw = getCookie('cart');
			return raw ? JSON.parse(raw) : {};
		} catch(e) {
			return {};
		}
	}
	function saveCart(obj) {
		var consent = getCookie('cookie_consent');
		// si refus, ne pas sauvegarder
		if (consent === 'rejected') return;
		setCookie('cart', JSON.stringify(obj), 30);
		// Optionally update cart count in header if an element exists
		updateCartCountUI();
	}
	function addToCart(gameId, qty) {
		qty = qty || 1;
		var cart = getCart();
		if (!cart[gameId]) cart[gameId] = 0;
		cart[gameId] += qty;
		saveCart(cart);
	}

	// Wishlist logic (array of ids)
	function getWishlist() {
		try {
			var raw = getCookie('wishlist');
			return raw ? JSON.parse(raw) : [];
		} catch(e) {
			return [];
		}
	}
	function saveWishlist(list) {
		var consent = getCookie('cookie_consent');
		if (consent === 'rejected') return;
		setCookie('wishlist', JSON.stringify(list), 30);
	}
	function toggleWishlist(gameId) {
		var list = getWishlist();
		var idx = list.indexOf(gameId);
		if (idx === -1) {
			list.push(gameId);
		} else {
			list.splice(idx, 1);
		}
		saveWishlist(list);
		return list.indexOf(gameId) !== -1;
	}

	// Update any header/cart-count element with attribute data-cart-count or id "cart-count"
	function updateCartCountUI() {
		var cart = getCart();
		var total = 0;
		for (var k in cart) {
			if (Object.prototype.hasOwnProperty.call(cart, k)) total += Number(cart[k]) || 0;
		}
		var els = document.querySelectorAll('[data-cart-count], #cart-count');
		els.forEach(function(el){
			el.textContent = total;
		});
	}

	// Bind buttons
	function bindButtons() {
		// add-to-cart
		document.querySelectorAll('.add-to-cart').forEach(function(btn){
			btn.addEventListener('click', function(e){
				var id = btn.getAttribute('data-game-id');
				if (!id) return;
				// si consent non défini, demander
				var consent = getCookie('cookie_consent');
				if (!consent) {
					showConsentDialogThen(function(ok){
						if (ok) addToCart(id);
					});
					return;
				}
				// si rejeté, on peut montrer un message sans sauvegarder
				if (consent === 'rejected') {
					// simple feedback
					alert("Vous avez refusé les cookies : le panier ne sera pas conservé entre les sessions.");
					return;
				}
				addToCart(id);
				// feedback visuel minimal
				btn.classList.add('added');
				setTimeout(function(){ btn.classList.remove('added'); }, 900);
			});
		});
		// wishlist toggle
		document.querySelectorAll('.toggle-wishlist').forEach(function(btn){
			btn.addEventListener('click', function(e){
				var id = btn.getAttribute('data-game-id');
				if (!id) return;
				var consent = getCookie('cookie_consent');
				if (!consent) {
					showConsentDialogThen(function(ok){
						if (ok) {
							var active = toggleWishlist(id);
							btn.classList.toggle('active', active);
						}
					});
					return;
				}
				if (consent === 'rejected') {
					alert("Vous avez refusé les cookies : la wishlist ne sera pas conservée entre les sessions.");
					return;
				}
				var active = toggleWishlist(id);
				btn.classList.toggle('active', active);
			});
		});
	}

	// Utility to show consent first then callback
	function showConsentDialogThen(cb) {
		var el = document.getElementById('cookie-consent');
		if (el) {
			el.style.display = 'block';
			// listen for accept/reject click once
			var accept = document.getElementById('accept-cookies');
			var reject = document.getElementById('reject-cookies');
			function cleanup() {
				if (accept) accept.removeEventListener('click', onAccept);
				if (reject) reject.removeEventListener('click', onReject);
			}
			function onAccept(){
				acceptCookies();
				cleanup();
				cb(true);
			}
			function onReject(){
				rejectCookies();
				cleanup();
				cb(false);
			}
			if (accept) accept.addEventListener('click', onAccept);
			if (reject) reject.addEventListener('click', onReject);
		} else {
			// fallback: accept by default
			acceptCookies();
			cb(true);
		}
	}

	// Init
	document.addEventListener('DOMContentLoaded', function(){
		showConsentIfNeeded();
		bindButtons();
		updateCartCountUI();
		// wire direct accept/reject buttons if present
		var accept = document.getElementById('accept-cookies');
		var reject = document.getElementById('reject-cookies');
		if (accept) accept.addEventListener('click', acceptCookies);
		if (reject) reject.addEventListener('click', rejectCookies);
	});
})();
