/**
 * Sends a write request to the API.
 *
 * The API lives on cPanel, whose mod_security rejects PUT, PATCH and DELETE
 * outright — the request never reaches Laravel, it comes straight back as
 * "403 Forbidden". Verified against production: PUT, PATCH and DELETE all
 * 403, POST gets through. That silently broke every edit and delete screen in
 * this admin: changing a password, publishing a review, deleting a category
 * or a language, saving the home page or the menu.
 *
 * So writes travel as POST carrying Laravel's `_method` override, which the
 * framework unwraps back into the intended verb before routing. Correct with
 * or without the host filtering, so it survives a hosting change.
 */
export async function apiWrite<T = any>(
  url: string,
  method: 'PUT' | 'PATCH' | 'DELETE' | 'POST',
  body: Record<string, any> = {},
  extraHeaders: Record<string, string> = {},
): Promise<T> {
  const payload = method === 'POST' ? body : { ...body, _method: method }

  return await $fetch<T>(url, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${localStorage.getItem('auth_token') || ''}`,
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...extraHeaders,
    },
    body: payload,
  })
}
