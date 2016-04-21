"=============================================================================
" AUTHOR:  Mun Mun Das <m2mdas at gmail.com>
" FILE: symfony_tags.vim
" Last Modified: September 10, 2013
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

" let s:Cache = unite#util#get_vital().import('System.Cache')
let s:Cache = unite#util#get_vital_cache()

function! unite#sources#symfony_tags#define() "{{{
    let sources = [ s:symfony_tags]
    return sources
endfunction"}}}

let s:symfony_tags = {
            \ 'name' : 'symfony/tags',
            \ 'description' : 'List Symfony tags',
            \ 'hooks' : {},
            \ 'default_kind' : '',
            \ }

function! s:symfony_tags.gather_candidates(args, context) "{{{
    if !phpcomplete_extended#symfony#is_valid_project()
        return []
    endif
    return s:get_tag_menu_entries(a:args, a:context)
endfunction"}}}

function! s:get_tag_menu_entries(args, context) "{{{
    let args = a:args
    let context = a:context
    let tag_services = keys(phpcomplete_extended#symfony#get_tag_services())
    if empty(tag_services)
        return []
    endif
    let tag_services = sort(tag_services)
    let candidates = map(deepcopy(tag_services), "{
                \ 'word' : v:val,
                \ 'abbr' : v:val,
                \ 'kind' : 'source',
                \ 'action__source_name' : 'symfony/services',
                \ 'action__source_args' : ['', v:val]
                \ }"
            \)
    return candidates
endfunction "}}}

let &cpo = s:save_cpo
unlet s:save_cpo

" vim: foldmethod=marker:expandtab:ts=4:sts=4:tw=78
