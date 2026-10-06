export type UploadFolder = 'products' | 'banners' | 'categories';

const xsrfToken = () => decodeURIComponent(document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '');

/** Uploads one image to the admin and resolves with its public URL; rejects with a readable message. */
export async function uploadImage(file: File, folder: UploadFolder = 'products'): Promise<string> {
    const body = new FormData();
    body.append('image', file);
    body.append('folder', folder);

    const response = await fetch('/admin/uploads/images', {
        method: 'POST',
        body,
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
    });
    const json = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = json.errors?.image?.[0] ?? json.message;
        throw new Error(response.status === 413 ? 'That file is too large to upload.' : message || `Could not upload ${file.name}.`);
    }

    return json.url as string;
}
