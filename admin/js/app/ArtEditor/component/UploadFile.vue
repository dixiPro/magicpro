<script setup>
import { ref, computed } from "vue";

const emit = defineEmits(["uploaded"]);

const props = defineProps({
    path: { type: String, required: true },
    directory: { type: Object, required: true }, // [{ name, type, mime, size, mtime }]
});

const show = ref(false);
const uploading = ref(false);
const uploads = ref([]); // [{ name, progress, status }]

// Список имён уже существующих файлов (для проверки дубликатов)
const existingNames = computed(() => {
    return new Set((props.directory ?? []).map(it => (it.name ?? "").toLowerCase()));
});

function open() {
    show.value = true;
}

function close() {
    show.value = false;
    uploads.value = [];
}

// ============================
// Загрузка одного файла через XHR
// ============================
function uploadFile(file, index) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();

        const params = new URLSearchParams({
            command: "uploadBin",
            path: props.path,
            filename: file.name,
        });

        xhr.open("POST", `/a_dmin/api/fileManager?${params.toString()}`, true);
        xhr.responseType = "json";

        // Индивидуальный прогресс
        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                uploads.value[index].progress = Math.round((e.loaded / e.total) * 100);
            }
        };

        xhr.onload = () => {
            const res = xhr.response;
            uploads.value[index].progress = 100;
            uploads.value[index].status =
                xhr.status === 200 && res?.status === true ? "ok" : "error";

            if (uploads.value[index].status === "ok") {
                emit("uploaded", res.data);
                resolve(res.data);
            } else {
                const msg =
                    res?.errorMsg || res?.message || `Ошибка загрузки (${xhr.status})`;
                reject(new Error(msg));
            }
        };

        xhr.onerror = () => {
            uploads.value[index].status = "error";
            reject(new Error("Ошибка сети при загрузке файла"));
        };

        xhr.onabort = () => {
            uploads.value[index].status = "error";
            reject(new Error("Загрузка отменена"));
        };

        xhr.setRequestHeader("Content-Type", "application/octet-stream");
        xhr.send(file);
    });
}

// ============================
// Обработчик загрузки
// ============================
async function onUpload(event) {
    const files = Array.isArray(event?.files) ? event.files : [];
    if (!files.length) return;

    uploads.value = files.map((f) => ({
        name: f.name,
        progress: 0,
        status: "loading",
    }));

    uploading.value = true;

    await Promise.all(
        files.map(async (f, i) => {
            try {
                await uploadFile(f, i);
            } catch (error) {
                document.showToast(error.message || "Ошибка загрузки файла", "error");
            }
        })
    );

    uploading.value = false;
    uploader.value?.clear();
}

// Проверка на дубликат
function isDuplicate(name) {
    return existingNames.value.has(name.toLowerCase());
}

function getPreviewURL(file) {
    return URL.createObjectURL(file);
}


function isImageFile(file) {
    return !!file && typeof file.type === "string" && file.type.startsWith("image/");
}
const uploader = ref(null);

defineExpose({ open, close });
</script>

<template>
    <Dialog v-model:visible="show" modal header="Загрузка файлов" class="w-50">
        <FileUpload name="files[]" mode="advanced" multiple chooseLabel="Выбрать файлы" uploadLabel="Загрузить"
            cancelLabel="Отмена" customUpload :auto="false" :maxFileSize="500000000" ref="uploader"
            @uploader="onUpload">
            <template #content="{ files }">
                <div v-for="(file, i) in files" :key="file.name" class="mb-2">
                    <div>
                        <div class="d-flex">
                            <div>
                                <img v-if="isImageFile(file)" :src="getPreviewURL(file)" alt=""
                                    style="width: 96px; height: auto;" class="me-2 rounded border" />

                            </div>
                            <div class="flex-grow-1">
                                <div><small>{{ file.name }}</small></div>
                                <div v-if="isDuplicate(file.name)" class="text-danger">
                                    копия — будет переписан
                                </div>
                                <div>
                                    <small v-if="uploads[i]">{{ uploads[i].progress }}%</small>
                                </div>
                            </div>

                            <div v-if="isDuplicate(file.name)" class="text-danger ms-2">
                                <i class="fas fa-times pointer" @click="files.splice(i, 1)"></i>
                            </div>
                        </div>

                    </div>

                    <div class="progress mt-2" style="height: 10px;">
                        <div class="progress-bar" :class="{
                            'bg-success': uploads[i]?.status === 'ok',
                            'bg-danger': uploads[i]?.status === 'error',
                            'progress-bar-striped progress-bar-animated':
                                uploads[i]?.status === 'loading'
                        }" role="progressbar" :style="{ width: (uploads[i]?.progress || 0) + '%' }"></div>
                    </div>
                </div>
            </template>
        </FileUpload>
    </Dialog>
</template>
