import errno
import json
import os
import subprocess


def iterate_subdirectories(root):
    """Generator over the child directories of a given directory."""
    for name in os.listdir(root):
        subdir = os.path.join(root, name)
        if os.path.isdir(subdir):
            yield subdir


def cache_git_info():
    """Create JSON cache files of git branch information.

    :param cfg: Dict of global configuration values
    :raises: :class:`IOError` if version directory is not found
    """
    branch_dir = os.path.join(os.curdir, 'main')

    if not os.path.isdir(branch_dir):
        raise IOError(errno.ENOENT, 'Invalid branch directory', branch_dir)

    # Create cache directory if needed
    cache_dir = os.path.join(branch_dir, 'cache', 'gitinfo')
    if not os.path.isdir(cache_dir):
        os.mkdir(cache_dir)

    # Create cache for branch
    info = git_info(branch_dir)
    cache_file = git_info_filename(branch_dir, branch_dir, cache_dir)
    with open(cache_file, 'w') as f:
        json.dump(info, f)

    # Create cache for each extension and skin
    for dirname in ['extensions', 'skins', '../mw-config']:
        dir = os.path.join(branch_dir, dirname)
        for subdir in iterate_subdirectories(dir):
            try:
                info = git_info(subdir)
            except (IOError, subprocess.CalledProcessError) as e:
                pass
            else:
                cache_file = git_info_filename(
                    subdir, branch_dir, cache_dir).replace("deployment-staging", "mediawiki")
                with open(cache_file, 'w') as f:
                    json.dump(info, f)


def get_disclosable_head(repo_directory):
    """Get the SHA1 of the most recent commit that can be publicly disclosed.
    If a commit only exists locally, it is considered private. This function
    will try to get the tip of the remote tracking branch, and fall back to
    the common ancestor of HEAD and origin."""
    with open(os.devnull, 'wb') as dev_null:
        try:
            return subprocess.check_output(
                ('/usr/bin/git', 'rev-list', '-1', '@{upstream}'),
                cwd=repo_directory, stderr=dev_null).strip()
        except subprocess.CalledProcessError:
            remote = subprocess.check_output(
                ('/usr/bin/git', 'remote'),
                cwd=repo_directory, stderr=dev_null).strip()
            return subprocess.check_output(
                ('/usr/bin/git', 'merge-base', 'HEAD', remote),
                cwd=repo_directory, stderr=dev_null).strip()


def git_info(directory):
    """Compute git version information for a given directory that is
    compatible with MediaWiki's GitInfo class.

    :param directory: Directory to scan for git information
    :returns: Dict of information about current repository state
    """
    git_dir = os.path.join(directory, '.git')
    if not os.path.exists(git_dir):
        raise IOError(errno.ENOENT, '.git not found', directory)

    if os.path.isfile(git_dir):
        # submodules
        with open(git_dir, 'r') as f:
            git_ref = f.read().strip()

        if not git_ref.startswith('gitdir: '):
            raise IOError(errno.EINVAL, 'Unexpected .git contents', git_dir)
        git_ref = git_ref[8:]
        if git_ref[0] != '/':
            git_ref = os.path.abspath(os.path.join(directory, git_ref))
        git_dir = git_ref

    head_file = os.path.join(git_dir, 'HEAD')
    with open(head_file, 'r') as f:
        head = f.read().strip()
    if head.startswith('ref: '):
        head = head[5:]

    head_sha1 = get_disclosable_head(directory)
    print(head_sha1)
    commit_date = subprocess.check_output(
        ('/usr/bin/git', 'show', '-s', '--format=%ct', head_sha1),
        cwd=git_dir).strip()

    if head.startswith('refs/heads/'):
        branch = head[11:]
    else:
        branch = head

    # Requires git v1.7.5+
    remote_url = subprocess.check_output(
        ('/usr/bin/git', 'ls-remote', '--get-url'),
        cwd=git_dir).strip()

    return {
        '@directory': directory,
        'head': head,
        'headSHA1': head_sha1,
        'headCommitDate': commit_date,
        'branch': branch,
        'remoteURL': remote_url,
    }


def git_info_filename(directory, install_path, cache_path):
    """Compute the path for a git_info cache file related to a given
    directory.

    >>> git_info_filename('foo', 'foo', '')
    'info.json'
    >>> git_info_filename('foo/bar/baz', 'foo', 'xyzzy')
    'xyzzy/info-bar-baz.json'
    """
    path = directory
    if path.startswith(install_path):
        path = path[len(install_path):]
    return os.path.join(cache_path, 'info%s.json' % path.replace('/', '-'))


cache_git_info()
