const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content') ?? '';

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let index = 0; index < rawData.length; index += 1) {
        outputArray[index] = rawData.charCodeAt(index);
    }

    return outputArray;
}

async function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    return navigator.serviceWorker.register('/sw.js');
}

async function subscribeUserToPush(registration) {
    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    const json = subscription.toJSON();

    await fetch('/parent/push-subscriptions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            endpoint: json.endpoint,
            keys: json.keys,
            content_encoding: 'aesgcm',
        }),
    });
}

async function unsubscribeUserFromPush(registration) {
    const subscription = await registration.pushManager.getSubscription();

    if (!subscription) {
        return;
    }

    await fetch('/parent/push-subscriptions', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            endpoint: subscription.endpoint,
        }),
    });

    await subscription.unsubscribe();
}

window.pushNotifications = {
    isSupported() {
        return 'serviceWorker' in navigator && 'PushManager' in window && vapidPublicKey !== '';
    },

    async enable() {
        if (!this.isSupported()) {
            return false;
        }

        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            return false;
        }

        const registration = await registerServiceWorker();

        if (!registration) {
            return false;
        }

        await subscribeUserToPush(registration);

        return true;
    },

    async disable() {
        if (!this.isSupported()) {
            return;
        }

        const registration = await navigator.serviceWorker.ready;
        await unsubscribeUserFromPush(registration);
    },
};

export {};
