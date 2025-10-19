export async function apiFile(data, logResult = false) {
    const url = "/a_dmin/api/fileManager";
    try {
        const response = await apiCall({
            url: url,
            data: data,
            logResult: logResult,
        });
        return response.data;
    } catch (e) {
        document.showToast(e, "error");
        throw new Error("ошибка");
    }
}

export async function apiArt(data, logResult = false) {
    const url = "/a_dmin/api/articles";
    try {
        const response = await apiCall({
            url: url,
            data: data,
            logResult: logResult,
        });
        return response.data;
    } catch (e) {
        document.showToast(e, "error");
        throw new Error("ошибка");
    }
}

export async function apiCall(params = {}) {
    const { url = "/", data = {}, method = "POST", logResult = false } = params;

    //
    return new Promise(async (resolve, reject) => {
        if (url == "") {
            reject(new Error("Ошибка в запросе"));
            return;
        }
        let response, apiResult; // Объявляем заранее, чтобы была доступна дальше

        if (logResult) {
            console.log("apiCall Start");
            console.log("apiCall url", url);
            console.log("apiCall data", data);
        }
        // Запрос
        try {
            response = await fetch(url, {
                method: method,
                headers: {
                    // 'X-Requested-With': 'XMLapiCallRequest',
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                // Если HTTP-статус не 2xx (например, 500, 404)
                reject(new Error(`${response?.status} ${response.statusText}`));
                return;
            }
        } catch (error) {
            reject(new Error("Ошибка сети"));
            return;
        }

        // Прасинг ответа
        try {
            apiResult = await response.json();
            if (logResult) {
                console.log("apiCall apiResult", apiResult);
            }
        } catch (error) {
            reject(new Error("Невалидный ответ"));
            return;
        } finally {
            if (logResult) {
                console.log("apiCall end", response);
            }
        }

        // Анализ ответа
        if (Boolean(apiResult.status)) {
            resolve(apiResult); // Успех: status === 1
            return;
        } else {
            reject(
                new Error(
                    apiResult?.errorMsg ||
                        apiResult?.result ||
                        "Неизвестная ошибка"
                )
            ); // Ошибка: status === 0
            return;
        }
    });
}

export function translitString(input) {
    const tab = {
        " ": "_",
        А: "A",
        Б: "B",
        В: "V",
        Г: "G",
        Д: "D",
        Е: "E",
        Ё: "E",
        Ж: "Zh",
        З: "Z",
        И: "I",
        Й: "J",
        К: "K",
        Л: "L",
        М: "M",
        Н: "N",
        О: "O",
        П: "P",
        Р: "R",
        С: "S",
        Т: "T",
        У: "U",
        Ф: "F",
        Х: "Kh",
        Ц: "Ts",
        Ч: "Ch",
        Ш: "Sh",
        Щ: "Shh",
        Ъ: "",
        Ы: "Y",
        Ь: "",
        Э: "E",
        Ю: "Yu ",
        Я: "Ya",
        а: "a",
        б: "b",
        в: "v",
        г: "g",
        д: "d",
        е: "e",
        ё: "e",
        ж: "zh",
        з: "z",
        и: "i",
        й: "j",
        к: "k",
        л: "l",
        м: "m",
        н: "n",
        о: "o",
        п: "p",
        р: "r",
        с: "s",
        т: "t",
        у: "u",
        ф: "f",
        х: "kh",
        ц: "ts",
        ч: "ch",
        ш: "sh",
        щ: "shh",
        ъ: "",
        ы: "y",
        ь: "",
        э: "e",
        ю: "yu",
        я: "ya",
        // Латиница (пропускается без изменений)
        a: "a",
        b: "b",
        c: "c",
        d: "d",
        e: "e",
        f: "f",
        g: "g",
        h: "h",
        i: "i",
        j: "j",
        k: "k",
        l: "l",
        m: "m",
        n: "n",
        o: "o",
        p: "p",
        q: "q",
        r: "r",
        s: "s",
        t: "t",
        u: "u",
        v: "v",
        w: "w",
        x: "x",
        y: "y",
        z: "z",
        A: "A",
        B: "B",
        C: "C",
        D: "D",
        E: "E",
        F: "F",
        G: "G",
        H: "H",
        I: "I",
        J: "J",
        K: "K",
        L: "L",
        M: "M",
        N: "N",
        O: "O",
        P: "P",
        Q: "Q",
        R: "R",
        S: "S",
        T: "T",
        U: "U",
        V: "V",
        W: "W",
        X: "X",
        Y: "Y",
        Z: "Z",
        // Цифры
        0: "0",
        1: "1",
        2: "2",
        3: "3",
        4: "4",
        5: "5",
        6: "6",
        7: "7",
        8: "8",
        9: "9",
        // Допустимые для URL
        "-": "-",
        _: "_",
    };

    const arr = input.split("").map((el) => tab[el] || "");
    return arr.join("");
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
