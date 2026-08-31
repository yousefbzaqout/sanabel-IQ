self.addEventListener('push', (event) => {
    let payload = {
        title: 'Sanabel IQ',
        body: 'لديك تحديث جديد',
        icon: '/icons/sanabel-icon.svg',
        badge: '/icons/sanabel-icon.svg',
        data: { url: '/dashboard' },
    };

    if (event.data) {
        try {
            payload = { ...payload, ...event.data.json() };
        } catch (error) {
            payload.body = event.data.text();
        }
    }

    const title = payload.title ?? 'Sanabel IQ';
    const options = {
        body: payload.body ?? '',
        icon: payload.icon ?? '/icons/sanabel-icon.svg',
        badge: payload.badge ?? '/icons/sanabel-icon.svg',
        dir: 'rtl',
        lang: 'ar',
        tag: payload.tag ?? 'sanabel-notification',
        data: payload.data ?? { url: '/dashboard' },
        actions: payload.actions ?? [],
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url ?? '/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }

            return undefined;
        }),
    );
});
