import { getRequestToken } from '@nextcloud/auth'
import { linkTo } from '@nextcloud/router'

export async function request(method, action, params) {
  const response = await fetch(
    linkTo('xwiki', 'index.php') + '/' + action,
    {
      method,
      headers: {
        requesttoken: getRequestToken()
      },
      ...params
    }
  );

  try {
    // return await is important to catch the error.
    // Just return does not work.
    return await response.json();
  } catch (e) {
    console.error(e);
  }
}
