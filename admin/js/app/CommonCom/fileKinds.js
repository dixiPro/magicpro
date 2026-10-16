/**
 * What a file is, by its name alone.
 *
 * The manager decides by this what a click opens: the media window or the text
 * editor. The text list is the list of the server — `validateEditExtension` of
 * API_FileManagerPostController — because a file the editor opens but cannot
 * save is worse than a file it does not open.
 */
import { getFileExtension } from '../apiCall';

const IMAGES = ['jpg', 'jpeg', 'jpe', 'jfif', 'png', 'gif', 'webp', 'avif', 'svg', 'ico'];
const VIDEO = ['mp4', 'webm', 'ogv', 'ogg', 'mov', 'm4v'];
const AUDIO = ['mp3', 'wav', 'oga', 'm4a', 'flac', 'aac'];
const TEXTS = [
  // plain text and markup
  'txt', 'rtf', 'md', 'html', 'htm', 'xhtml', 'xml', 'svg',
  // styles
  'css', 'scss', 'sass', 'less',
  // scripts
  'js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx', 'vue', 'map',
  // data
  'json', 'json5', 'jsonld', 'csv', 'tsv', 'sql', 'webmanifest',
  // the settings of the web server for this folder
  'htaccess',
];

/** image, video, audio, pdf — or an empty string, which is «not media». */
export function mediaKind(name) {
  const ext = getFileExtension(String(name ?? '')).toLowerCase();

  if (IMAGES.includes(ext)) return 'image';
  if (VIDEO.includes(ext)) return 'video';
  if (AUDIO.includes(ext)) return 'audio';
  if (ext === 'pdf') return 'pdf';

  return '';
}

/**
 * A file the editor may open and the server will take back.
 *
 * A name without a dot — LICENSE, CHANGELOG — is plain text for the server as
 * well, so it is editable here too.
 */
export function isEditable(name) {
  const text = String(name ?? '');

  if (!text.includes('.')) return true;

  return TEXTS.includes(getFileExtension(text).toLowerCase());
}
