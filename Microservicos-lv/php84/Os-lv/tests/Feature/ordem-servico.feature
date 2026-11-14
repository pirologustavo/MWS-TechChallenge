Feature: Ciclo de vida de uma ordem de serviço
  Como atendente da oficina
  Quero acompanhar a OS desde a abertura até a entrega
  Para manter o histórico e o status coerentes

  Scenario: Abrir, diagnosticar, aprovar, finalizar e entregar uma OS
    Given cliente, veículo, mecânico e peça estão disponíveis
    When a atendente abre a OS e registra o diagnóstico e orçamento
    And o cliente aprova o orçamento
    And a oficina finaliza e entrega o veículo
    Then a OS fica no status "Entregue"
    And o vínculo da peça permanece no histórico da OS

  # Implemented by OrdemServicoControllerTest::test_bdd_fluxo_completo_da_os_da_abertura_a_entrega.
