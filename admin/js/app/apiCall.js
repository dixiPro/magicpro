export async function apiFile(data, logResult = false) {
  const url = '/a_dmin/api/fileManager';
  try {
    const response = await apiCall({
      url: url,
      data: data,
      logResult: logResult,
    });
    return response.data;
  } catch (e) {
    document.showToast(e, 'error');
    throw new Error('ошибка');
  }
}

export async function apiArt(data, logResult = false) {
  const url = '/a_dmin/api/articles';
  try {
    const response = await apiCall({
      url: url,
      data: data,
      logResult: logResult,
    });
    return response.data;
  } catch (e) {
    document.showToast(e, 'error');
    throw new Error('ошибка');
  }
}

export async function apiCall(params = {}) {
  const { url = '/', data = {}, method = 'POST', logResult = false } = params;

  //
  return new Promise(async (resolve, reject) => {
    if (url == '') {
      reject(new Error('Ошибка в запросе'));
      return;
    }
    let response, apiResult; // Объявляем заранее, чтобы была доступна дальше

    if (logResult) {
      console.log('apiCall Start');
      console.log('apiCall url', url);
      console.log('apiCall data', data);
    }
    // Запрос
    try {
      response = await fetch(url, {
        method: method,
        headers: {
          // 'X-Requested-With': 'XMLapiCallRequest',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
      });

      if (!response.ok) {
        // Если HTTP-статус не 2xx (например, 500, 404)
        reject(new Error(`${response?.status} ${response.statusText}`));
        return;
      }
    } catch (error) {
      reject(new Error('Ошибка сети'));
      return;
    }

    // Прасинг ответа
    try {
      apiResult = await response.json();
      if (logResult) {
        console.log('apiCall apiResult', apiResult);
      }
    } catch (error) {
      reject(new Error('Невалидный ответ'));
      return;
    } finally {
      if (logResult) {
        console.log('apiCall end', response);
      }
    }

    // Анализ ответа
    if (Boolean(apiResult.status)) {
      resolve(apiResult); // Успех: status === 1
      return;
    } else {
      reject(new Error(apiResult?.errorMsg || apiResult?.result || 'Неизвестная ошибка')); // Ошибка: status === 0
      return;
    }
  });
}

export function translitString(input) {
  const tab = {
    ' ': '_',
    А: 'A',
    Б: 'B',
    В: 'V',
    Г: 'G',
    Д: 'D',
    Е: 'E',
    Ё: 'E',
    Ж: 'Zh',
    З: 'Z',
    И: 'I',
    Й: 'J',
    К: 'K',
    Л: 'L',
    М: 'M',
    Н: 'N',
    О: 'O',
    П: 'P',
    Р: 'R',
    С: 'S',
    Т: 'T',
    У: 'U',
    Ф: 'F',
    Х: 'Kh',
    Ц: 'Ts',
    Ч: 'Ch',
    Ш: 'Sh',
    Щ: 'Shh',
    Ъ: '',
    Ы: 'Y',
    Ь: '',
    Э: 'E',
    Ю: 'Yu ',
    Я: 'Ya',
    а: 'a',
    б: 'b',
    в: 'v',
    г: 'g',
    д: 'd',
    е: 'e',
    ё: 'e',
    ж: 'zh',
    з: 'z',
    и: 'i',
    й: 'j',
    к: 'k',
    л: 'l',
    м: 'm',
    н: 'n',
    о: 'o',
    п: 'p',
    р: 'r',
    с: 's',
    т: 't',
    у: 'u',
    ф: 'f',
    х: 'kh',
    ц: 'ts',
    ч: 'ch',
    ш: 'sh',
    щ: 'shh',
    ъ: '',
    ы: 'y',
    ь: '',
    э: 'e',
    ю: 'yu',
    я: 'ya',
    // Латиница (пропускается без изменений)
    a: 'a',
    b: 'b',
    c: 'c',
    d: 'd',
    e: 'e',
    f: 'f',
    g: 'g',
    h: 'h',
    i: 'i',
    j: 'j',
    k: 'k',
    l: 'l',
    m: 'm',
    n: 'n',
    o: 'o',
    p: 'p',
    q: 'q',
    r: 'r',
    s: 's',
    t: 't',
    u: 'u',
    v: 'v',
    w: 'w',
    x: 'x',
    y: 'y',
    z: 'z',
    A: 'A',
    B: 'B',
    C: 'C',
    D: 'D',
    E: 'E',
    F: 'F',
    G: 'G',
    H: 'H',
    I: 'I',
    J: 'J',
    K: 'K',
    L: 'L',
    M: 'M',
    N: 'N',
    O: 'O',
    P: 'P',
    Q: 'Q',
    R: 'R',
    S: 'S',
    T: 'T',
    U: 'U',
    V: 'V',
    W: 'W',
    X: 'X',
    Y: 'Y',
    Z: 'Z',
    // Цифры
    0: '0',
    1: '1',
    2: '2',
    3: '3',
    4: '4',
    5: '5',
    6: '6',
    7: '7',
    8: '8',
    9: '9',
    // Допустимые для URL
    '-': '-',
    _: '_',
  };

  const arr = input.split('').map((el) => tab[el] || '');
  return arr.join('');
}

export function setMagicIcon(color) {
  const svg = `
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="${color}">
      <path d="M13.0356 3.6516L12.4625 3.99097C11.8331 4.36373 11.5184 4.55011 11.1717 4.59353C10.825 4.63696 10.4855 4.53251 9.80666 4.3236L9.18861 4.13342C6.79965 3.39828 5.60517 3.03072 4.88716 3.68788C4.16916 4.34505 4.40313 5.59173 4.87109 8.08509L4.99216 8.73015C5.12513 9.43868 5.19162 9.79295 5.11275 10.139C5.03388 10.4851 4.81858 10.7839 4.38797 11.3813L3.99594 11.9253C2.48063 14.0278 1.72297 15.079 2.0926 15.9505C2.46223 16.8221 3.71435 16.9367 6.2186 17.1659L6.8665 17.2252C7.57812 17.2903 7.93394 17.3229 8.2319 17.4934C8.52986 17.6638 8.73623 17.9529 9.14897 18.5311L9.52473 19.0574C10.9772 21.092 11.7034 22.1092 12.6498 21.9907C13.5963 21.8722 14.1362 20.6963 15.2159 18.3446L15.4953 17.7362C15.6521 17.3947 15.7688 17.1404 15.8806 16.9413L20.4697 21.5303C20.7626 21.8232 21.2374 21.8232 21.5303 21.5303C21.8232 21.2374 21.8232 20.7626 21.5303 20.4697L17.0986 16.0379C17.2206 15.9935 17.3568 15.9458 17.5101 15.8921L18.1344 15.6735C20.5474 14.8284 21.7538 14.4059 21.9691 13.4611C22.1845 12.5163 21.266 11.675 19.4291 9.99234L18.9538 9.55701C18.4318 9.07887 18.1708 8.83978 18.0354 8.52053C17.9 8.20128 17.9055 7.838 17.9166 7.11145L17.9266 6.44993C17.9655 3.8931 17.9849 2.61468 17.1715 2.14931C16.3582 1.68395 15.2506 2.33983 13.0356 3.6516Z"/>
    </svg>
  `;
  const link = document.querySelector("link[rel*='icon']") || document.createElement('link');
  link.rel = 'icon';
  link.type = 'image/svg+xml';
  link.href = 'data:image/svg+xml,' + encodeURIComponent(svg);
    document.head.appendChild(link);
}
//
//
//
/* use
            try {
              response = await apiCall('/admin/design/?action=GetArt', {
                idPage: 'monaco',
              });
              console.log(response);
            } catch (e) {
              console.log(e);
            } finally {
            }

          

*/
