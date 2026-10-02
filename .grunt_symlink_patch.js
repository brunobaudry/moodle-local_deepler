const fs = require('fs');
const path = require('path');

const moodleRoot = process.cwd();

const origRealpathSync = fs.realpathSync;
const origRealpathSyncNative = fs.realpathSync.native || fs.realpathSync;

function safeRealpath(targetPath, options) {
    const absPath = path.isAbsolute(targetPath) ? path.normalize(targetPath) : path.normalize(path.resolve(moodleRoot, targetPath));
    const isRelative = !path.isAbsolute(targetPath);

    // If the path is inside Moodle root
    if (absPath.startsWith(moodleRoot)) {
        try {
            const resolved = origRealpathSync(targetPath, options);
            // If resolving it took it outside Moodle root (e.g. symlinked plugin in git_repos)
            if (!resolved.startsWith(moodleRoot)) {
                return isRelative ? path.relative(moodleRoot, absPath) || '.' : absPath;
            }
            return resolved;
        } catch (e) {
            return isRelative ? path.relative(moodleRoot, absPath) || '.' : absPath;
        }
    }

    return origRealpathSync(targetPath, options);
}

fs.realpathSync = function(targetPath, options) {
    return safeRealpath(targetPath, options);
};

if (fs.realpathSync.native) {
    fs.realpathSync.native = function(targetPath, options) {
        return safeRealpath(targetPath, options);
    };
}
