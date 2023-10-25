#!/usr/bin/env python
# coding: utf-8

import chevron
import logging
from pathlib import Path
from dataclasses import dataclass
from tokenizers import Tokenizer

from worker_framework.datatype import ChatRecord, Role
from abc import ABC, abstractmethod

logger = logging.getLogger(__name__)

@dataclass
class ChatTuple:
    """
    Grouped chat record for rendering prompt.
    """
    system:str = None
    user:str = None
    bot:str = None

class ChatTupleFactory:
    
    @staticmethod
    def from_chat_history(chat_history: [ChatRecord]) -> [ChatTuple]:
        """
        Convert a list of ChatRecords to a list o ChatTuple.
        Noticed that it's expected that a bot message is an immediate successor of a user message.
        """
        chat_tuples = []
        for chat in chat_history:
            if chat.role == Role.USER:
                chat_tuples.append(ChatTuple(user=chat.msg))
            elif chat.role == Role.BOT:
                chat_tuples[-1].bot = chat.msg
        return chat_tuples

class TaideLlm(ABC):
    """
    Common interface to invoke the TAIDE LLM.
    """

    def __init__(self,
                 token_limit = 3500,
                 prompt_template_path = 'prompt_template/taide.mustache',
                 tokenizer_path = '/llm/tokenizer.json',
                 ):

        self.input_token_limit = token_limit
        
        prompt_template_file = Path(prompt_template_path)
        self.prompt_template = prompt_template_file.read_text()
        self.tokenizer = Tokenizer.from_file(tokenizer_path)

    def is_too_long(self, chat_history: [ChatRecord]) -> bool:
        """
        Estimate whether the prompt generated by the given chat history will be too long.
        This public API can be use to evaluate how many documents can be placed in the context window.
        """
        
        chat_history = ChatTupleFactory.from_chat_history(chat_history)
        prompt = self.gen_prompt(chat_history)
        return self._is_too_long(prompt)[0]
    
    def _is_too_long(self, sentence: str) -> bool:
        """
        Calculate whether the number of tokens of given sentence exceeds the threshold.
        """

        tokens_without_bos = self.tokenizer.encode(sentence).tokens[1:]
        num_tokens = len(tokens_without_bos)
        return num_tokens >= self.input_token_limit, num_tokens

    def gen_prompt(self, chat_history: [ChatTuple], append_system: bool = True) -> str:
        """
        Generate prompt from given chat history.
        """

        system_chat_tuple = ChatTuple(
            system = 'You are a helpful assistant. 你是一個樂於助人的助手。',
            user = '請用中文回答我',
            # bot = '好! 我樂於助人,是你的好助手。' # b1.0.0
            bot = '當然!為方便溝通,我使用的是傳統中文語言。您有何請求或疑問,請慷慨請教我?'
        )

        if append_system:
            chat_history = [system_chat_tuple] + chat_history

        prompt = chevron.render(
            self.prompt_template,
            {'history': chat_history}
        )

        return prompt
    
    async def complete(self, chat_history: [ChatRecord]): 
        result = ''
        try:
            
            chat_history = ChatTupleFactory.from_chat_history(chat_history)
            logger.info('Data: {}'.format(chat_history))
            
            # Trim the over-length history
            prompt = ''
            tokens = 0
            while True:
                prompt = self.gen_prompt(chat_history)
                too_long, tokens = self._is_too_long(prompt)
                if not too_long: break
                chat_history = chat_history[1:]
            
            logger.info('Final Prompt ({} tokens):\n{}'.format(tokens, prompt))
            
            logger.info('Generating...')
            result, output_tokens = await self._complete(prompt, tokens)
            logger.info(f'Generation finished. Generated {output_tokens} tokens.')
            logger.debug(f'Reply: {result}')
            
        except Exception as e:
            result = ''
            logger.exception('Generation failed.')
            raise
        finally:
            return result
    
    @abstractmethod
    def _complete(self, prompt:str, tokens:int) -> (str, int):
        """
        The implementation of invoking the LLM to complete the prompt.
        Parameter:
        prompt: The complete prompt to the LLM.
        tokens: The number of tokens of the prompt.

        Return:
        The response from the LLM and number of output tokens.
        """
        pass

class TaideLlmFactory:
    @staticmethod
    def get_taide_llm(model_location:str = 'local', *args, **kwargs):
        if model_location == 'local':
            from .local_taide_llm import LocalTaideLlm
            return LocalTaideLlm(*args, **kwargs)
        elif model_location == 'remote-nchc':
            from .remote_taide_llm import NchcTaideLlm
            return NchcTaideLlm(*args, **kwargs)
        else:
            raise ValueError(f'Unknown model_location {model_location}')
            return None
