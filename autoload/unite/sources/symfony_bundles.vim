"=============================================================================
" AUTHOR:  Mun Mun Das <m2mdas at gmail.com>
" FILE: symfony_bundles.vim
" Last Modified: August 29, 2013
" License: MIT license  {{{
"     Permission is hereby granted, free of charge, to any person obtaining
"     a copy of this software and associated documentation files (the
"     "Software"), to deal in the Software without restriction, including
"     without limitation the rights to use, copy, modify, merge, publish,
"     distribute, sublicense, and/or sell copies of the Software, and to
"     permit persons to whom the Software is furnished to do so, subject to
"     the following conditions:
"
"     The above copyright notice and this permission notice shall be included
"     in all copies or substantial portions of the Software.
"
"     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
"     OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
"     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
"     IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
"     CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
"     TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
"     SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
" }}}
"=============================================================================

let s:save_cpo = &cpo
set cpo&vim

let s:Cache = unite#util#get_vital().import('System.Cache')

function! unite#sources#symfony_bundles#define() "{{{
    let sources = [ s:symfony_bundles]
    return sources
endfunction"}}}

let s:symfony_bundles = {
            \ 'name' : 'symfony/bundles',
            \ 'description' : 'Lists symfony bundles',
            \ 'hooks' : {},
            \ }

function! s:symfony_bundles.gather_candidates(args, context) "{{{
    let entries = []
    if !phpcomplete_extended#symfony#is_valid_project()
        return []
    endif
    return s:get_bundle_menu_entries(a:args, a:context)
endfunction"}}}

function! s:get_bundle_menu_entries(args, context) "{{{
    let bundles = phpcomplete_extended#symfony#get_bundles()
    if empty(bundles)
        return []
    endif
    let bundle_keys = sort(keys(bundles))
    let candidates = map(bundle_keys, "{
                \ 'word' : v:val,
                \ 'abbr' : v:val,
                \ 'kind' : 'directory',
                \ 'action__path' : bundles[v:val].bundle_dir,
                \ 'action__directory' : bundles[v:val].bundle_dir
                \ }"
            \)
    return candidates
endfunction "}}}

let &cpo = s:save_cpo
unlet s:save_cpo

" vim: foldmethod=marker:expandtab:ts=4:sts=4:tw=78

